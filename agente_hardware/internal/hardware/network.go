package hardware

import (
	"net"
	"strings"

	"github.com/yusufpapurcu/wmi"
)

type Win32_NetworkAdapterConfiguration struct {
	Description      string
	IPAddress        []string
	IPEnabled        bool
	MACAddress       string
	DefaultIPGateway []string
}

// CollectNetwork finds the primary active network adapter, filtering out virtual interfaces
func CollectNetwork() (NetworkInfo, error) {
	var configs []Win32_NetworkAdapterConfiguration
	q := "SELECT Description, IPAddress, IPEnabled, MACAddress, DefaultIPGateway FROM Win32_NetworkAdapterConfiguration WHERE IPEnabled = True"

	info := NetworkInfo{}

	if err := wmi.Query(q, &configs); err != nil {
		// Fallback to Go standard net package
		return collectNetworkFallback()
	}

	// Filter valid physical adapters
	var candidates []Win32_NetworkAdapterConfiguration

	for _, cfg := range configs {
		if !cfg.IPEnabled || len(cfg.IPAddress) == 0 || cfg.MACAddress == "" {
			continue
		}

		if isVirtualAdapter(cfg.Description) {
			continue
		}

		// Prefer adapter with DefaultIPGateway
		if len(cfg.DefaultIPGateway) > 0 {
			candidates = append([]Win32_NetworkAdapterConfiguration{cfg}, candidates...)
		} else {
			candidates = append(candidates, cfg)
		}
	}

	if len(candidates) > 0 {
		selected := candidates[0]
		info.Description = CleanString(selected.Description)
		info.AdapterName = info.Description
		info.MACAddress = strings.ToUpper(CleanString(selected.MACAddress))

		// Extract primary IPv4
		for _, ip := range selected.IPAddress {
			ipClean := strings.TrimSpace(ip)
			parsed := net.ParseIP(ipClean)
			if parsed != nil && parsed.To4() != nil && !parsed.IsLoopback() {
				info.IPAddress = ipClean
				break
			}
		}

		descLower := strings.ToLower(info.Description)
		info.IsWireless = strings.Contains(descLower, "wi-fi") ||
			strings.Contains(descLower, "wireless") ||
			strings.Contains(descLower, "802.11") ||
			strings.Contains(descLower, "wlan")

		return info, nil
	}

	return collectNetworkFallback()
}

func isVirtualAdapter(description string) bool {
	lower := strings.ToLower(description)
	virtualKeywords := []string{
		"virtual",
		"vmware",
		"virtualbox",
		"vethernet",
		"hyper-v",
		"tap-",
		"tap0",
		"vpn",
		"loopback",
		"bluetooth",
		"tailscale",
		"zerotier",
		"wireguard",
		"npcap",
		"wi-fi direct",
		"pseudo",
		"wsl",
		"tunnel",
	}

	for _, kw := range virtualKeywords {
		if strings.Contains(lower, kw) {
			return true
		}
	}

	return false
}

// collectNetworkFallback uses Go's standard library net package if WMI network query returns nothing
func collectNetworkFallback() (NetworkInfo, error) {
	info := NetworkInfo{}

	ifaces, err := net.Interfaces()
	if err != nil {
		return info, err
	}

	for _, iface := range ifaces {
		// Ignore down or loopback interfaces
		if iface.Flags&net.FlagUp == 0 || iface.Flags&net.FlagLoopback != 0 {
			continue
		}

		if isVirtualAdapter(iface.Name) {
			continue
		}

		mac := iface.HardwareAddr.String()
		if mac == "" {
			continue
		}

		addrs, err := iface.Addrs()
		if err != nil {
			continue
		}

		for _, addr := range addrs {
			var ip net.IP
			switch v := addr.(type) {
			case *net.IPNet:
				ip = v.IP
			case *net.IPAddr:
				ip = v.IP
			}

			if ip != nil && ip.To4() != nil && !ip.IsLoopback() {
				info.IPAddress = ip.String()
				info.MACAddress = strings.ToUpper(mac)
				info.AdapterName = iface.Name
				info.Description = iface.Name
				return info, nil
			}
		}
	}

	return info, nil
}
