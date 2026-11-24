package hardware

import (
	"fmt"
	"math"
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type Win32_DiskDrive struct {
	DeviceID      string
	Model         string
	Size          uint64
	MediaType     string
	InterfaceType string
	SerialNumber  string
}

type MSFT_PhysicalDisk struct {
	FriendlyName string
	MediaType    uint16 // 0: Unspecified, 3: HDD, 4: SSD, 5: SCM
	BusType      uint16 // 7: USB, 8: RAID, 11: SATA, 17: NVMe
	Size         uint64
}

// CollectDisks retrieves physical hard drives and SSDs, calculating sizes and identifying media type
func CollectDisks() ([]DiskDrive, error) {
	var drives []Win32_DiskDrive
	qDrives := "SELECT DeviceID, Model, Size, MediaType, InterfaceType, SerialNumber FROM Win32_DiskDrive"

	if err := wmi.Query(qDrives, &drives); err != nil {
		return nil, err
	}

	// Attempt to query MSFT_PhysicalDisk from root\Microsoft\Windows\Storage for authoritative SSD/HDD tagging
	msftMap := make(map[string]MSFT_PhysicalDisk)
	var msftDisks []MSFT_PhysicalDisk
	qMSFT := "SELECT FriendlyName, MediaType, BusType, Size FROM MSFT_PhysicalDisk"
	if err := wmi.QueryNamespace(qMSFT, &msftDisks, `root\Microsoft\Windows\Storage`); err == nil {
		for _, md := range msftDisks {
			key := strings.ToLower(CleanString(md.FriendlyName))
			msftMap[key] = md
		}
	}

	result := make([]DiskDrive, 0, len(drives))

	for _, d := range drives {
		// Omit empty size or zero bytes (e.g. card readers, empty media)
		if d.Size == 0 {
			continue
		}

		cleanedModel := CleanString(d.Model)
		cleanedSerial := CleanSerialNumber(d.SerialNumber)
		cleanedInterface := CleanString(d.InterfaceType)

		// Size calculation
		sizeGB := float64(d.Size) / (1024 * 1024 * 1024)
		formattedSize := FormatBytesToHuman(d.Size)

		// Determine Media Type (SSD vs HDD vs NVMe)
		mediaType := detectMediaType(cleanedModel, cleanedInterface, d.MediaType, msftMap)

		formatted := fmt.Sprintf("%s %s (%s)", mediaType, cleanedModel, formattedSize)

		disk := DiskDrive{
			Model:         cleanedModel,
			SizeBytes:     d.Size,
			SizeGB:        math.Round(sizeGB*100) / 100,
			SizeFormatted: formattedSize,
			MediaType:     mediaType,
			InterfaceType: cleanedInterface,
			SerialNumber:  cleanedSerial,
			Formatted:     formatted,
		}

		result = append(result, disk)
	}

	return result, nil
}

func detectMediaType(model, iface, win32MediaType string, msftMap map[string]MSFT_PhysicalDisk) string {
	lowerModel := strings.ToLower(model)
	lowerIface := strings.ToLower(iface)

	// 1. Check MSFT_PhysicalDisk mapping if available
	for nameKey, md := range msftMap {
		if strings.Contains(lowerModel, nameKey) || strings.Contains(nameKey, lowerModel) {
			if md.BusType == 17 { // NVMe
				return "NVMe SSD"
			}
			if md.MediaType == 4 { // SSD
				return "SSD"
			}
			if md.MediaType == 3 { // HDD
				return "HDD"
			}
		}
	}

	// 2. BusType / Interface check
	if strings.Contains(lowerIface, "nvme") || strings.Contains(lowerModel, "nvme") {
		return "NVMe SSD"
	}

	// 3. String keywords in model name
	ssdKeywords := []string{
		"ssd", "solid state", "kingston sa", "crucial", "sandisk", "kioxia",
		"micron", "adata", "western digital wds", "wd blue sn", "wd green",
		"samsung 850", "samsung 860", "samsung 870", "samsung 970", "samsung 980", "samsung 990",
		"lexar", "pny", "patriot", "transcend",
	}
	for _, kw := range ssdKeywords {
		if strings.Contains(lowerModel, kw) {
			return "SSD"
		}
	}

	hddKeywords := []string{
		"st1000", "st2000", "st500", "wd10", "wd20", "wd500", "hgst", "hitachi",
		"toshiba dt", "toshiba mq", "barracuda", "seagate", "caviar", "spinpoint",
	}
	for _, kw := range hddKeywords {
		if strings.Contains(lowerModel, kw) {
			return "HDD"
		}
	}

	// Fallback to Win32_DiskDrive MediaType
	if strings.Contains(strings.ToLower(win32MediaType), "fixed hard disk") {
		return "HDD"
	}

	return "HDD"
}
