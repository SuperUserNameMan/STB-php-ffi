# STB-php-ffi
PHP 8 FFI binding to several [nothings/stb](https://github.com/nothings/stb) libraries.

---

__[nothings/stb](https://github.com/nothings/stb)__ is a cool collection of C/C++ single file public domain libraries.

__STB-php-ffi__ is a binding and a wrapper to :

- [stb_image.h](https://github.com/nothings/stb/blob/master/stb_image.h)
- [stb_image_resize.h](https://github.com/nothings/stb/blob/master/stb_image_resize.h)
- [stb_image_write.h](https://github.com/nothings/stb/blob/master/stb_image_write.h)
- [stb_rect_pack.h](https://github.com/nothings/stb/blob/master/stb_rect_pack.h)
- [stb_truetype.h](https://github.com/nothings/stb/blob/master/stb_truetype.h)
- [stb_vorbis.c](https://github.com/nothings/stb/blob/master/stb_vorbis.c)


---

`STB::` can be used to call any included stb API :

- `STB::stbi_*`       : API from `stb_image.h` and `stb_image_write.h`
- `STB::stbir_*`      : API from `stb_image_resize.h`
- `STB::stbrp_*`      : API from `stb_rect_pack.h`
- `STB::stbtt_*`      : API from `stb_truetype.h`
- `STB::stb_vorbis_*` : API from `stb_vorbis.c`

`STBI::*` is a shortcut to both `STB::stbi_*` and to `STB::stbir_*` ; it also contains their constants and helpers.

`TTF::*` is a shortcut to `STB::stbtt_*`, and also contains dedicated constants and helpers.

`Vorbis::*` is a shortcut to `STB::stb_vorbis_*` and contains dedicated constants and helpers.
