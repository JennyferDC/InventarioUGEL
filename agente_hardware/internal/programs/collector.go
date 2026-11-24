package programs

import (
	"sort"
	"strings"

	"golang.org/x/sys/windows/registry"
)

// InstalledProgram represents a software detected on the machine
type InstalledProgram struct {
	Name        string `json:"nombre"`
	Version     string `json:"version,omitempty"`
	Publisher   string `json:"fabricante,omitempty"`
	InstallDate string `json:"fecha_instalacion,omitempty"`
}

// CollectInstalledPrograms scans standard Windows Registry locations for installed software
func CollectInstalledPrograms() ([]InstalledProgram, error) {
	uniquePrograms := make(map[string]InstalledProgram)

	paths := []struct {
		root registry.Key
		path string
	}{
		{registry.LOCAL_MACHINE, `SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall`},
		{registry.LOCAL_MACHINE, `SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall`},
		{registry.CURRENT_USER, `Software\Microsoft\Windows\CurrentVersion\Uninstall`},
	}

	for _, p := range paths {
		k, err := registry.OpenKey(p.root, p.path, registry.READ)
		if err != nil {
			continue
		}

		subkeys, err := k.ReadSubKeyNames(-1)
		k.Close()
		if err != nil {
			continue
		}

		for _, subkeyName := range subkeys {
			subk, err := registry.OpenKey(p.root, p.path+`\`+subkeyName, registry.READ)
			if err != nil {
				continue
			}

			displayName, _, err := subk.GetStringValue("DisplayName")
			if err != nil || strings.TrimSpace(displayName) == "" {
				subk.Close()
				continue
			}

			displayName = strings.TrimSpace(displayName)

			// Filter out Windows updates and system components
			if isSystemComponentOrUpdate(subk, displayName) {
				subk.Close()
				continue
			}

			displayVersion, _, _ := subk.GetStringValue("DisplayVersion")
			publisher, _, _ := subk.GetStringValue("Publisher")
			installDate, _, _ := subk.GetStringValue("InstallDate")
			subk.Close()

			key := strings.ToLower(displayName)
			if _, exists := uniquePrograms[key]; !exists {
				uniquePrograms[key] = InstalledProgram{
					Name:        displayName,
					Version:     strings.TrimSpace(displayVersion),
					Publisher:   strings.TrimSpace(publisher),
					InstallDate: strings.TrimSpace(installDate),
				}
			}
		}
	}

	result := make([]InstalledProgram, 0, len(uniquePrograms))
	for _, prog := range uniquePrograms {
		result = append(result, prog)
	}

	sort.Slice(result, func(i, j int) bool {
		return strings.ToLower(result[i].Name) < strings.ToLower(result[j].Name)
	})

	return result, nil
}

func isSystemComponentOrUpdate(k registry.Key, name string) bool {
	// Check SystemComponent flag
	if val, _, err := k.GetIntegerValue("SystemComponent"); err == nil && val == 1 {
		return true
	}

	// Check ParentKeyName (sub-components or patches)
	if parent, _, err := k.GetStringValue("ParentKeyName"); err == nil && strings.TrimSpace(parent) != "" {
		return true
	}

	// Check ReleaseType
	if relType, _, err := k.GetStringValue("ReleaseType"); err == nil {
		lower := strings.ToLower(relType)
		if strings.Contains(lower, "update") || strings.Contains(lower, "hotfix") {
			return true
		}
	}

	// Ignore common Windows update prefixes
	lowerName := strings.ToLower(name)
	if strings.HasPrefix(lowerName, "kb") && len(lowerName) >= 8 && isNumeric(lowerName[2:8]) {
		return true
	}
	if strings.HasPrefix(lowerName, "security update") || strings.HasPrefix(lowerName, "actualización") {
		return true
	}

	return false
}

func isNumeric(s string) bool {
	for _, r := range s {
		if r < '0' || r > '9' {
			return false
		}
	}
	return true
}
