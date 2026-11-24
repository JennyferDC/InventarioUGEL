package hardware

import (
	"fmt"
	"math"
	"regexp"
	"strings"
)

var multipleSpacesRegex = regexp.MustCompile(`\s+`)

// CleanString removes extra whitespace and trims edges
func CleanString(s string) string {
	s = strings.TrimSpace(s)
	return multipleSpacesRegex.ReplaceAllString(s, " ")
}

// CleanSerialNumber filters out boilerplate/dummy OEM serial numbers
func CleanSerialNumber(s string) string {
	cleaned := CleanString(s)
	lower := strings.ToLower(cleaned)

	invalidValues := []string{
		"to be filled by o.e.m.",
		"to be filled by oem",
		"default string",
		"none",
		"null",
		"system serial number",
		"chassis serial number",
		"0123456789",
		"1234567890",
		"n/a",
		"not applicable",
		"unknown",
		"all",
	}

	for _, invalid := range invalidValues {
		if lower == invalid || strings.Contains(lower, "to be filled") {
			return ""
		}
	}

	return cleaned
}

// FormatBytesToHuman converts bytes to an intuitive string (GB, TB, MB)
func FormatBytesToHuman(bytes uint64) string {
	if bytes == 0 {
		return "0 GB"
	}

	const (
		gb = 1024 * 1024 * 1024
		tb = 1024 * gb
	)

	// Round disk sizes commonly to commercial standards (e.g. 512 GB, 256 GB, 1 TB, 480 GB, 120 GB)
	if bytes >= tb {
		val := float64(bytes) / float64(tb)
		if math.Abs(val-math.Round(val)) < 0.15 {
			return fmt.Sprintf("%.0f TB", math.Round(val))
		}
		return fmt.Sprintf("%.1f TB", val)
	}

	val := float64(bytes) / float64(gb)
	// If close to an integer (e.g. 15.8 GB -> 16 GB, 238.4 GB -> 240 GB or 256 GB)
	if math.Abs(val-math.Round(val)) < 0.25 {
		return fmt.Sprintf("%.0f GB", math.Round(val))
	}

	// Known commercial SSD/HDD sizes
	commercialSizes := []float64{120, 128, 240, 250, 256, 480, 500, 512, 960, 1000}
	for _, target := range commercialSizes {
		if math.Abs(val-target) <= (target * 0.08) {
			return fmt.Sprintf("%.0f GB", target)
		}
	}

	return fmt.Sprintf("%.0f GB", math.Round(val))
}

// FormatRAMSize formats RAM bytes specifically (RAM is strictly powers of 2)
func FormatRAMSize(bytes uint64) (float64, string) {
	if bytes == 0 {
		return 0, "0 GB"
	}
	gbVal := float64(bytes) / (1024 * 1024 * 1024)
	rounded := math.Round(gbVal)
	return rounded, fmt.Sprintf("%.0f GB", rounded)
}
