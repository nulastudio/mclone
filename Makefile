OUTPUT=Build/tools
BINARY_WIN_X86=win-x86/mclone.exe
BINARY_WIN_X64=win-x64/mclone.exe
BINARY_LINUX_X64=linux-x64/mclone
BINARY_MAC_X64=osx-x64/mclone
BUILD_FLAGS=-ldflags="-s -w"

build-all: build-win-x86 build-win-x64 build-linux-x64 build-osx-x64

build-win-x86:
	CGO_ENABLED=0 GOOS=windows GOARCH=386 go build $(BUILD_FLAGS) -o ./$(OUTPUT)/$(BINARY_WIN_X86) github.com/nulastudio/mclone/src/main

build-win-x64:
	CGO_ENABLED=0 GOOS=windows GOARCH=amd64 go build $(BUILD_FLAGS) -o ./$(OUTPUT)/$(BINARY_WIN_X64) github.com/nulastudio/mclone/src/main

build-linux-x64:
	CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build $(BUILD_FLAGS) -o ./$(OUTPUT)/$(BINARY_LINUX_X64) github.com/nulastudio/mclone/src/main

build-osx-x64:
	CGO_ENABLED=0 GOOS=darwin GOARCH=amd64 go build $(BUILD_FLAGS) -o ./$(OUTPUT)/$(BINARY_MAC_X64) github.com/nulastudio/mclone/src/main
