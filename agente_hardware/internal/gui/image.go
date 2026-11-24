package gui

import (
	_ "embed"
	"syscall"
	"unsafe"
)

//go:embed assets/logo.png
var ugelLogoPNG []byte

type GdiplusStartupInput struct {
	GdiplusVersion           uint32
	DebugEventCallback       uintptr
	SuppressBackgroundThread int32
	SuppressExternalCodecs   int32
}

type BITMAPINFOHEADER struct {
	BiSize          uint32
	BiWidth         int32
	BiHeight        int32
	BiPlanes        uint16
	BiBitCount      uint16
	BiCompression   uint32
	BiSizeImage     uint32
	BiXPelsPerMeter int32
	BiYPelsPerMeter int32
	BiClrUsed       uint32
	BiClrImportant  uint32
}

type ICONINFO struct {
	FIcon    int32
	XHotspot uint32
	YHotspot uint32
	HbmMask  uintptr
	HbmColor uintptr
}

var (
	gdiplus = syscall.NewLazyDLL("gdiplus.dll")
	ole32   = syscall.NewLazyDLL("ole32.dll")

	pGdiplusStartup            = gdiplus.NewProc("GdiplusStartup")
	pGdiplusShutdown           = gdiplus.NewProc("GdiplusShutdown")
	pGdipCreateBitmapFromStream = gdiplus.NewProc("GdipCreateBitmapFromStream")
	pGdipDisposeImage          = gdiplus.NewProc("GdipDisposeImage")
	pGdipCreateFromHDC         = gdiplus.NewProc("GdipCreateFromHDC")
	pGdipDeleteGraphics        = gdiplus.NewProc("GdipDeleteGraphics")
	pGdipDrawImageRectI        = gdiplus.NewProc("GdipDrawImageRectI")
	pGdipSetInterpolationMode  = gdiplus.NewProc("GdipSetInterpolationMode")
	pGdipGetImageWidth         = gdiplus.NewProc("GdipGetImageWidth")
	pGdipGetImageHeight        = gdiplus.NewProc("GdipGetImageHeight")

	pCreateStreamOnHGlobal = ole32.NewProc("CreateStreamOnHGlobal")
	pGlobalAlloc           = kernel32.NewProc("GlobalAlloc")
	pGlobalLock            = kernel32.NewProc("GlobalLock")
	pGlobalUnlock          = kernel32.NewProc("GlobalUnlock")

	pCreateDIBSection    = gdi32.NewProc("CreateDIBSection")
	pCreateBitmap        = gdi32.NewProc("CreateBitmap")
	pCreateIconIndirect  = user32.NewProc("CreateIconIndirect")
	pDestroyIcon         = user32.NewProc("DestroyIcon")
)

const (
	GMEM_MOVEABLE                      = 0x0002
	InterpolationModeHighQualityBicubic = 7
)

type ImageManager struct {
	token            uintptr
	hBitmap          uintptr
	hTaskbarIcon     uintptr // Icono minimalista de computadora con fondo azul institucional
	hTransparentIcon uintptr // Icono 100% transparente para eliminar el icono del título
	width            int
	height           int
}

func initGdiplus() (*ImageManager, error) {
	var token uintptr
	input := GdiplusStartupInput{GdiplusVersion: 1}
	status, _, _ := pGdiplusStartup.Call(uintptr(unsafe.Pointer(&token)), uintptr(unsafe.Pointer(&input)), 0)
	if status != 0 {
		return nil, syscall.Errno(status)
	}

	mgr := &ImageManager{
		token:            token,
		hTaskbarIcon:     createComputerTaskbarIcon(),
		hTransparentIcon: createTransparentIcon(16, 16),
	}

	if len(ugelLogoPNG) > 0 {
		hMem, _, _ := pGlobalAlloc.Call(GMEM_MOVEABLE, uintptr(len(ugelLogoPNG)))
		if hMem != 0 {
			ptr, _, _ := pGlobalLock.Call(hMem)
			if ptr != 0 {
				copy(unsafe.Slice((*byte)(unsafe.Pointer(ptr)), len(ugelLogoPNG)), ugelLogoPNG)
				pGlobalUnlock.Call(hMem)

				var pStream uintptr
				hr, _, _ := pCreateStreamOnHGlobal.Call(hMem, 1, uintptr(unsafe.Pointer(&pStream)))
				if hr == 0 && pStream != 0 {
					var pBmp uintptr
					st, _, _ := pGdipCreateBitmapFromStream.Call(pStream, uintptr(unsafe.Pointer(&pBmp)))
					if st == 0 && pBmp != 0 {
						mgr.hBitmap = pBmp
						var w, h uint32
						pGdipGetImageWidth.Call(pBmp, uintptr(unsafe.Pointer(&w)))
						pGdipGetImageHeight.Call(pBmp, uintptr(unsafe.Pointer(&h)))
						mgr.width = int(w)
						mgr.height = int(h)
					}
				}
			}
		}
	}

	return mgr, nil
}

func (m *ImageManager) DrawLogo(hdc uintptr, x, y, destWidth, destHeight int) {
	if m == nil || m.hBitmap == 0 {
		return
	}
	var graphics uintptr
	st, _, _ := pGdipCreateFromHDC.Call(hdc, uintptr(unsafe.Pointer(&graphics)))
	if st != 0 || graphics == 0 {
		return
	}
	defer pGdipDeleteGraphics.Call(graphics)

	pGdipSetInterpolationMode.Call(graphics, uintptr(InterpolationModeHighQualityBicubic))
	pGdipDrawImageRectI.Call(
		graphics,
		m.hBitmap,
		uintptr(x),
		uintptr(y),
		uintptr(destWidth),
		uintptr(destHeight),
	)
}

func (m *ImageManager) Release() {
	if m == nil {
		return
	}
	if m.hTaskbarIcon != 0 {
		pDestroyIcon.Call(m.hTaskbarIcon)
		m.hTaskbarIcon = 0
	}
	if m.hTransparentIcon != 0 {
		pDestroyIcon.Call(m.hTransparentIcon)
		m.hTransparentIcon = 0
	}
	if m.hBitmap != 0 {
		pGdipDisposeImage.Call(m.hBitmap)
		m.hBitmap = 0
	}
	if m.token != 0 {
		pGdiplusShutdown.Call(m.token)
		m.token = 0
	}
}

// createTransparentIcon genera un icono de dimensiones w x h con canal alfa en 0 (invisible).
// Permite eliminar limpiamente el icono de la esquina superior izquierda de la ventana.
func createTransparentIcon(w, h int) uintptr {
	bmi := BITMAPINFOHEADER{
		BiSize:     uint32(unsafe.Sizeof(BITMAPINFOHEADER{})),
		BiWidth:    int32(w),
		BiHeight:   -int32(h), // top-down
		BiPlanes:   1,
		BiBitCount: 32,
	}

	var bits unsafe.Pointer
	hbmColor, _, _ := pCreateDIBSection.Call(0, uintptr(unsafe.Pointer(&bmi)), 0, uintptr(unsafe.Pointer(&bits)), 0, 0)
	if hbmColor == 0 {
		return 0
	}
	defer pDeleteObject.Call(hbmColor)

	hbmMask, _, _ := pCreateBitmap.Call(uintptr(w), uintptr(h), 1, 1, 0)
	if hbmMask == 0 {
		return 0
	}
	defer pDeleteObject.Call(hbmMask)

	ii := ICONINFO{
		FIcon:    1,
		HbmMask:  hbmMask,
		HbmColor: hbmColor,
	}

	hIcon, _, _ := pCreateIconIndirect.Call(uintptr(unsafe.Pointer(&ii)))
	return hIcon
}

// createComputerTaskbarIcon crea un icono minimalista de computadora con fondo azul institucional (#1E3A8A).
// Para la barra de tareas de Windows (32x32 ARGB).
func createComputerTaskbarIcon() uintptr {
	const size = 32
	bmi := BITMAPINFOHEADER{
		BiSize:     uint32(unsafe.Sizeof(BITMAPINFOHEADER{})),
		BiWidth:    size,
		BiHeight:   -size, // top-down DIB
		BiPlanes:   1,
		BiBitCount: 32,
	}

	var bits unsafe.Pointer
	hbmColor, _, _ := pCreateDIBSection.Call(0, uintptr(unsafe.Pointer(&bmi)), 0, uintptr(unsafe.Pointer(&bits)), 0, 0)
	if hbmColor == 0 {
		return 0
	}
	defer pDeleteObject.Call(hbmColor)

	pixels := unsafe.Slice((*uint32)(bits), size*size)

	// Paleta cromática en memoria DIB 32bpp (BGRA):
	// Azul institucional UGEL #1E3A8A -> B: 138 (0x8A), G: 58 (0x3A), R: 30 (0x1E), A: 255 (0xFF)
	colAzulUGEL := uint32(0xFF000000 | (30 << 16) | (58 << 8) | 138)
	colWhite := uint32(0xFFFFFFFF)
	colScreenInner := uint32(0xFF000000 | (18 << 16) | (38 << 8) | 90) // Tono azul oscuro para la pantalla interior

	// 1. Fondo en forma de cuadrado redondeado (radio 6px) con el color azul institucional
	for y := 0; y < size; y++ {
		for x := 0; x < size; x++ {
			inBadge := true
			if x < 6 && y < 6 {
				dx, dy := 6-x, 6-y
				if dx*dx+dy*dy > 36 {
					inBadge = false
				}
			} else if x >= size-6 && y < 6 {
				dx, dy := x-(size-7), 6-y
				if dx*dx+dy*dy > 36 {
					inBadge = false
				}
			} else if x < 6 && y >= size-6 {
				dx, dy := 6-x, y-(size-7)
				if dx*dx+dy*dy > 36 {
					inBadge = false
				}
			} else if x >= size-6 && y >= size-6 {
				dx, dy := x-(size-7), y-(size-7)
				if dx*dx+dy*dy > 36 {
					inBadge = false
				}
			}

			if inBadge {
				pixels[y*size+x] = colAzulUGEL
			} else {
				pixels[y*size+x] = 0 // Transparente
			}
		}
	}

	// 2. Monitor minimalista de computadora en blanco
	// Pantalla exterior: X de 6 a 25, Y de 6 a 19 (ancho 20px, alto 14px)
	for y := 6; y <= 19; y++ {
		for x := 6; x <= 25; x++ {
			if x == 6 || x == 7 || x == 24 || x == 25 || y == 6 || y == 7 || y == 18 || y == 19 {
				pixels[y*size+x] = colWhite
			} else {
				pixels[y*size+x] = colScreenInner
			}
		}
	}

	// Cuello del soporte: X de 14 a 17, Y de 20 a 22 (ancho 4px, alto 3px)
	for y := 20; y <= 22; y++ {
		for x := 14; x <= 17; x++ {
			pixels[y*size+x] = colWhite
		}
	}

	// Base del soporte: X de 10 a 21, Y de 23 a 24 (ancho 12px, alto 2px)
	for y := 23; y <= 24; y++ {
		for x := 10; x <= 21; x++ {
			pixels[y*size+x] = colWhite
		}
	}

	// Máscara 1-bit
	hbmMask, _, _ := pCreateBitmap.Call(size, size, 1, 1, 0)
	if hbmMask == 0 {
		return 0
	}
	defer pDeleteObject.Call(hbmMask)

	ii := ICONINFO{
		FIcon:    1,
		HbmMask:  hbmMask,
		HbmColor: hbmColor,
	}

	hIcon, _, _ := pCreateIconIndirect.Call(uintptr(unsafe.Pointer(&ii)))
	return hIcon
}
