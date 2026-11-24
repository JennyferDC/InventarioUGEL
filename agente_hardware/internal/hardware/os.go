package hardware

import (
	"fmt"
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type Win32_OperatingSystem struct {
	Caption        string
	Version        string
	BuildNumber    string
	OSArchitecture string
	InstallDate    string
}

// CollectOS extracts Windows edition, version, build number, and architecture
func CollectOS() (OSInfo, error) {
	var osList []Win32_OperatingSystem
	q := "SELECT Caption, Version, BuildNumber, OSArchitecture, InstallDate FROM Win32_OperatingSystem"

	info := OSInfo{
		Caption:      "Microsoft Windows",
		Architecture: "64-bit",
	}

	if err := wmi.Query(q, &osList); err != nil || len(osList) == 0 {
		return info, err
	}

	primary := osList[0]
	cleanedCaption := CleanString(primary.Caption)
	cleanedCaption = strings.TrimPrefix(cleanedCaption, "Microsoft ")

	arch := CleanString(primary.OSArchitecture)
	if strings.Contains(arch, "64") {
		arch = "64-bit"
	} else if strings.Contains(arch, "32") {
		arch = "32-bit"
	}

	info.Caption = cleanedCaption
	info.Version = CleanString(primary.Version)
	info.BuildNumber = CleanString(primary.BuildNumber)
	info.Architecture = arch
	info.InstallDate = CleanString(primary.InstallDate)

	buildText := ""
	if info.BuildNumber != "" {
		buildText = fmt.Sprintf(" (Compilación %s)", info.BuildNumber)
	}

	info.Formatted = fmt.Sprintf("%s %s%s", info.Caption, info.Architecture, buildText)

	return info, nil
}
