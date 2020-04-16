$workingdir = $pwd
$builddir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$rootdir = "${builddir}/.."
$tooldir = "${builddir}/tools"
$archivedir = "${builddir}/archive"

# 清理
rm -rf $tooldir
rm -rf $archivedir

# 编译
cd $rootdir
make

# 打包
mkdir ${archivedir}
"win-x86", "win-x64", "linux-x64", "osx-x64" | ForEach-Object -Process {
    $rid = $_
    cd "${tooldir}/${rid}"
    Compress-Archive -Force -Path * -DestinationPath "${archivedir}/${rid}.zip"
}

cd $workingdir
