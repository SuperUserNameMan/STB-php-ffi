#include <stdio.h>
#include <math.h>
#define STB_RECT_PACK_IMPLEMENTATION
#include "../stb/stb_rect_pack.h"
#define STB_TRUETYPE_IMPLEMENTATION
#include "../stb/stb_truetype.h"
#define STB_IMAGE_IMPLEMENTATION
#include "../stb/stb_image.h"
#define STB_IMAGE_WRITE_IMPLEMENTATION
#include "../stb/stb_image_write.h"
#define STB_IMAGE_RESIZE_IMPLEMENTATION
#define STBIR_PROGRESS_REPORT(val)   stbir_set_progress_report(val)
extern void  stbir_set_progress_report( float val );
extern float stbir_get_progress_report();
#include "../stb/stb_image_resize.h"
static float _val = 0.0;
void  stbir_set_progress_report( float val ) { _val = val ; }
float stbir_get_progress_report() { return _val ; }
#include "../stb/stb_vorbis.c"
