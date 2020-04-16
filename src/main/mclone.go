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
	"strings"
	"syscall"
	"time"

	"github.com/bitly/go-simplejson"
)

var forever = 100 * 12 * 30 * 24 * time.Hour

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
	for _, value := range os.Args[1:] {
		value = strings.Trim(value, " ")
		if value == "" {
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

	encRepo := base64.StdEncoding.EncodeToString([]byte(repo))

	// mclone
	var token string
	var mirror string
	{
		code, msg, data, err := jsonRequest(host+"/clone", map[string]string{
			"repo": encRepo,
		})
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

		fmt.Println("镜像成功，等待代码同步完成...")
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
	{
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
	}

	// clone
	if !quit && success {
		fmt.Println("同步成功，等待代码拉取完成...")

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
	{
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
