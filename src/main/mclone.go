package main

import (
	"context"
	"encoding/base64"
	"fmt"
	"io/ioutil"
	"net/http"
	"os"
	"os/exec"
	"os/signal"
	"path/filepath"
	"sort"
	"strconv"
	"strings"
	"syscall"
	"time"

	"github.com/bitly/go-simplejson"
)

var forever = 100 * 12 * 30 * 24 * time.Hour

var rootDir = filepath.Dir(os.Args[0])

var mirrors = map[string]string{
	"cnpm":     "github.com.cnpmjs.org",
	"gitclone": "gitclone.com",
}

var mirrorName = ""
var mirrorHost = ""
var mirrorFile = rootDir + "/mirror.conf"
var proxyFile = rootDir + "/proxy.conf"

func runCommand(command string, arguments []string, pwd string, timeout time.Duration) <-chan error {
	errchan := make(chan error, 1)

	ctx, cancel := context.WithTimeout(context.Background(), timeout)
	cmd := exec.CommandContext(ctx, command, arguments...)
	cmd.Dir = pwd
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr

	if err := cmd.Start(); err != nil {
		errchan <- err
	}

	go func() {
		if cmd.Process != nil && cmd.ProcessState == nil {
			if err := cmd.Wait(); err != nil {
				errchan <- err
			} else {
				errchan <- nil
			}
			cancel()
		}
	}()

	return errchan
}

func sendRequest(url string, data map[string]string) ([]byte, error) {
	http.DefaultClient.Timeout = 30 * time.Second

	var response *http.Response
	var err error
	if data != nil {
		var form = make(map[string][]string)
		for key := range data {
			form[key] = []string{data[key]}
		}
		response, err = http.PostForm(url, form)
	} else {
		response, err = http.Get(url)
	}

	if err != nil {
		return nil, err
	}
	if response.StatusCode != 200 {
		return nil, fmt.Errorf("unexpected status code %d", response.StatusCode)
	}

	defer response.Body.Close()
	bytes, err := ioutil.ReadAll(response.Body)
	if err != nil {
		return nil, err
	}

	return bytes, nil
}

func jsonRequest(url string, data map[string]string) (int, string, *simplejson.Json, error) {
	bytes, err := sendRequest(url, data)
	if err != nil {
		return 0, "", nil, err
	}

	json, err := simplejson.NewJson(bytes)
	if err != nil {
		return 0, "", nil, err
	}

	errCode := json.Get("err_no").MustInt(0)
	errMsg := json.Get("err_msg").MustString("")
	retData := json.Get("data")

	return errCode, errMsg, retData, nil
}

func repoName(repo string) string {
	parts := strings.Split(strings.TrimRight(repo, "/"), "/")
	for i := len(parts) - 1; i >= 0; i-- {
		part := strings.TrimRight(parts[i], ".git")
		if part == "" {
			continue
		}
		return part
	}
	return ""
}

func main() {
	sig := make(chan os.Signal)
	quit := false
	signal.Notify(sig, syscall.SIGHUP, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT)
	go func() {
		for s := range sig {
			switch s {
			case syscall.SIGHUP, syscall.SIGINT, syscall.SIGTERM, syscall.SIGQUIT:
				quit = true
			default:
			}
		}
	}()

	var host = "https://mclone.nulastudio.org"
	// var host = "http://localhost:8080"

	if len(os.Args) >= 2 && os.Args[1] == "mirror" {
		mirrorSubCommand()
		return
	}

	if len(os.Args) >= 2 && os.Args[1] == "proxy" {
		proxySubCommand()
		return
	}

	{
		_, err := os.Stat(mirrorFile)

		if err == nil || os.IsExist(err) {
			if mirror, err := ioutil.ReadFile(mirrorFile); err == nil {
				mName := strings.TrimSpace(string(mirror))
				if mHost, ok := mirrors[mName]; ok {
					mirrorName = mName
					mirrorHost = mHost
				}
			}
		}
	}

	{
		_, err := os.Stat(proxyFile)

		if err == nil || os.IsExist(err) {
			if proxy, err := ioutil.ReadFile(proxyFile); err == nil {
				host = strings.TrimSpace(string(proxy))
			}
		}
	}

	// parse
	valueArgs := []string{
		"--reference", "--reference-if-able",
		"--server-option",
		"-o", "--origin",
		"-b", "--branch",
		"-u", "--upload-pack",
		"--template",
		"-c", "--config",
		"--depth",
		"--shallow-since", "--shallow-exclude",
		"--recurse-submodules",
		"--separate-git-dir",
		"-j", "--jobs",
	}
	args, repo, dir, val4pre := []string{}, "", "", false
	safeClone := false
	unsafeClone := false
	settingProxy := false
	settingMirror := false
	useMirror := mirrorHost != ""
	for _, value := range os.Args[1:] {
		value = strings.Trim(value, " ")
		if value == "" {
			continue
		}
		if value == "--safe" {
			safeClone = true
			continue
		}
		if value == "--unsafe" {
			unsafeClone = true
			continue
		}
		if value == "--proxy" {
			settingProxy = true
			continue
		}
		if value == "--mirror" {
			settingMirror = true
			continue
		}
		if settingProxy {
			host = value
			settingProxy = false
			continue
		}
		if settingMirror {
			if ok, mHost := trySetMirror(value); ok {
				mirrorName = value
				mirrorHost = mHost
				useMirror = true
			} else {
				useMirror = false
			}
			settingMirror = false
			continue
		}
		if strings.HasPrefix(value, "-") || val4pre {
			args = append(args, value)
			val4pre = false
			for _, valueArg := range valueArgs {
				if value == valueArg {
					val4pre = true
					break
				}
			}
		} else if repo == "" {
			repo = value
		} else {
			dir = value
		}
	}
	if repo == "" {
		fmt.Println("empty repository")
		return
	}

	// mclone
	var token string
	var mirror string
	if !useMirror {
		encRepo := base64.StdEncoding.EncodeToString([]byte(repo))

		params := map[string]string{
			"repo": encRepo,
		}
		if safeClone {
			params["safe"] = "1"
		}
		if unsafeClone {
			params["unsafe"] = "1"
		}
		code, msg, data, err := jsonRequest(host+"/clone", params)
		if err != nil {
			fmt.Printf("无法镜像仓库：%s\n", err.Error())
			return
		}
		if code != 0 {
			fmt.Printf("无法镜像仓库：%d - %s\n", code, msg)
			return
		}
		token = data.Get("token").MustString("")
		mirror = data.Get("repo").MustString("")
		safe := data.Get("safe").MustBool(false)
		force := data.Get("force").MustBool(false)
		safeInfo := ""
		if safe {
			safeInfo = "安全"
		}
		if force {
			safeInfo = "强制安全"
		}

		fmt.Printf("%s镜像成功，等待代码同步完成...\n", safeInfo)
	}

	// status
	timeout := 30 * time.Minute
	tFirstInfo := 3 * time.Minute
	tSecondInfo := 10 * time.Minute
	tTick := 1
	tInfos := 0
	tFeedback := false
	timestart := time.Now()

	var success bool
	if !useMirror {
		allTimes := 5
		errTimes := 0
		fmt.Println("检查镜像仓库状态中...")
		for _tk, timenow := true, time.Now(); _tk || timenow.Sub(timestart) <= timeout; _tk = false {
			if quit {
				break
			}
			if tInfos == 0 && timenow.Sub(timestart) >= tFirstInfo {
				fmt.Println("您貌似在尝试镜像一个大型仓库，这通常会花费几分钟不等，请耐心等待同步完成")
				if !tFeedback {
					fmt.Println("如果您相信这耗时完全超出你预期，您可以尝试前往项目仓库提交反馈：https://github.com/nulastudio/mclone/issues/new")
					tFeedback = true
				}
				tInfos++
				tTick = 3
			}
			if tInfos == 1 && timenow.Sub(timestart) >= tSecondInfo {
				fmt.Println("您貌似在尝试镜像一个超大型仓库，这通常会花费十几分钟不等，请坐和放宽")
				if !tFeedback {
					fmt.Println("如果您相信这耗时完全超出你预期，您可以尝试前往项目仓库提交反馈：https://github.com/nulastudio/mclone/issues/new")
					tFeedback = true
				}
				tInfos++
				tTick = 10
			}

			code, msg, data, err := jsonRequest(host+"/status", map[string]string{
				"token": token,
			})
			if err != nil {
				fmt.Printf("检查镜像仓库状态出错：%s\n", err.Error())
				if errTimes == allTimes {
					break
				}
				errTimes++
			}
			if code != 0 {
				fmt.Printf("检查镜像仓库状态出错：%d - %s\n", code, msg)
				if errTimes == allTimes {
					break
				}
				errTimes++
			}
			if data != nil {
				status := data.Get("status").MustInt(999)
				if status == 1 {
					success = true
					break
				} else if status != -1 {
					fmt.Printf("检查镜像仓库状态出错：%d\n", status)
					if errTimes == allTimes {
						break
					}
					errTimes++
				}
			}
			timetake := time.Now().Sub(timenow)
			if timetake < time.Duration(tTick)*time.Second {
				time.Sleep(time.Duration(tTick)*time.Second - timetake)
			}
			timenow = time.Now()
		}
	} else {
		success = true
	}

	// clone
	if !quit && success {
		if !useMirror {
			fmt.Println("同步成功，等待代码拉取完成...")
		} else {
			// third-part mirror doesn't support SSH, replace to HTTPS
			mirror = repo
			fmt.Printf("使用第三方镜像拉取仓库中：%s\n", mirrorName)
			isSSH := !strings.HasPrefix(mirror, "https") && !strings.HasPrefix(mirror, "http")
			if isSSH {
				fmt.Println("第三方镜像方式不支持SSH方式，更换至HTTPS中...")
				mirror = strings.Replace(mirror, "git@github.com:", "https://github.com/", 1)
			}
			switch mirrorName {
			case "cnpm":
				mirror = strings.Replace(mirror, "github.com", mirrorHost, 1)
				break
			case "gitclone":
				mirror = strings.Replace(mirror, "github.com", mirrorHost+"/github.com", 1)
				break
			}
		}

		args = append(args, mirror)
		if dir == "" {
			// 取出repo name
			dir = repoName(repo)
		}
		if dir != "" {
			args = append(args, dir)
		}

		pwd, _ := os.Getwd()
		repopwd, _ := filepath.Abs(pwd + "/" + dir)

		{
			cmd := runCommand("git", append([]string{"clone"}, args...), pwd, forever)
			err := <-cmd
			if !quit && err != nil {
				success = false
			}
		}
		if !quit {
			cmd := runCommand("git", []string{"remote", "set-url", "origin", repo}, repopwd, forever)
			err := <-cmd
			if !quit && err != nil {
				success = false
			}
		}
	}

	// drop
	if !useMirror {
		code, msg, _, err := jsonRequest(host+"/drop", map[string]string{
			"token": token,
		})
		if err != nil {
			fmt.Printf("清理镜像仓库出错：%s\n", err.Error())
			return
		}
		if code != 0 {
			fmt.Printf("清理镜像仓库出错：%d - %s\n", code, msg)
			return
		}
	}

	if !quit && success {
		fmt.Println("mclone成功，enjoy it！")
	}
}

func mirrorSubCommand() {
	subcommand := ""
	if len(os.Args) >= 3 {
		subcommand = os.Args[2]
	}
	if subcommand == "list" {
		var names []string
		for name := range mirrors {
			names = append(names, name)
		}
		sort.Strings(names)
		length := longest(names)
		fmt.Println("Mirrors:")
		for _, name := range names {
			fmt.Printf("%-"+strconv.Itoa(length)+"s    %s\n", name, mirrors[name])
		}
	} else if subcommand == "set" {
		if len(os.Args) >= 4 {
			mName := os.Args[3]
			ok, _ := trySetMirror(mName)
			if ok {
				err := ioutil.WriteFile(mirrorFile, []byte(mName), 0666)
				ok = err == nil
			}
			if ok {
				fmt.Println("set mirror succeeded.")
			} else {
				fmt.Println("set mirror failed.")
			}
		}
	} else if subcommand == "del" {
		if err := os.Remove(mirrorFile); err == nil {
			fmt.Println("delete proxy succeeded.")
		} else {
			fmt.Println("delete proxy failed.")
		}
	} else {
		fmt.Println("list")
		fmt.Println("    list all supported third-part mirrors.")
		fmt.Println("set <mirror>")
		fmt.Println("    set and store mclone third-part mirror.")
		fmt.Println("del")
		fmt.Println("    delete stored mclone third-part mirror.")
	}
}

func proxySubCommand() {
	subcommand := ""
	if len(os.Args) >= 3 {
		subcommand = os.Args[2]
	}
	if subcommand == "set" {
		if len(os.Args) >= 4 {
			proxy := os.Args[3]
			if err := ioutil.WriteFile(proxyFile, []byte(proxy), 0666); err == nil {
				fmt.Println("set proxy succeeded.")
			} else {
				fmt.Println("set proxy failed.")
			}
		}
	} else if subcommand == "del" {
		if err := os.Remove(proxyFile); err == nil {
			fmt.Println("delete proxy succeeded.")
		} else {
			fmt.Println("delete proxy failed.")
		}
	} else {
		fmt.Println("set <proxy>")
		fmt.Println("    set and store mclone proxy server.")
		fmt.Println("del")
		fmt.Println("    delete stored mclone proxy server.")
	}
}

func trySetMirror(mName string) (bool, string) {
	var mHost = ""
	var ok = false
	if mHost, ok = mirrors[mName]; ok {
		mirrorHost = mHost
	} else {
		fmt.Printf("unknown mirror %s.\n", mName)
	}
	return ok, mHost
}

func longest(a []string) int {
	var l []string
	if len(a) > 0 {
		l = append(l, a[0])
		a = a[1:]
	}
	for _, s := range a {
		if len(l[0]) <= len(s) {
			if len(l[0]) < len(s) {
				l = l[:0]
			}
			l = append(l, s)
		}
	}
	return len(append([]string(nil), l...)[0])
}
