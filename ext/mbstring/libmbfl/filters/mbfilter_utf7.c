/*
 * "streamable kanji code filter and converter"
 * Copyright (c) 1998-2002 HappySize, Inc. All rights reserved.
 *
 * LICENSE NOTICES
 *
 * This file is part of "streamable kanji code filter and converter",
 * which is distributed under the terms of GNU Lesser General Public
 * License (version 2) as published by the Free Software Foundation.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with "streamable kanji code filter and converter";
 * if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307  USA
 *
 * The author of this file:
 *
 */
/*
 * The source code included in this files was separated from mbfilter.c
 * by moriyoshi koizumi <moriyoshi@php.net> on 4 dec 2002.
 *
 */

#include "mbfilter.h"
#include "mbfilter_utf7.h"

static int mbfl_filt_conv_utf7_wchar_flush(mbfl_convert_filter *filter);

static const unsigned char mbfl_base64_table[] = {
 /* 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', */
   0x41,0x42,0x43,0x44,0x45,0x46,0x47,0x48,0x49,0x4a,0x4b,0x4c,0x4d,
 /* 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', */
   0x4e,0x4f,0x50,0x51,0x52,0x53,0x54,0x55,0x56,0x57,0x58,0x59,0x5a,
 /* 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', */
   0x61,0x62,0x63,0x64,0x65,0x66,0x67,0x68,0x69,0x6a,0x6b,0x6c,0x6d,
 /* 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', */
   0x6e,0x6f,0x70,0x71,0x72,0x73,0x74,0x75,0x76,0x77,0x78,0x79,0x7a,
 /* '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/', '\0' */
   0x30,0x31,0x32,0x33,0x34,0x35,0x36,0x37,0x38,0x39,0x2b,0x2f,0x00
};

static const char *mbfl_encoding_utf7_aliases[] = {"utf7", NULL};

const mbfl_encoding mbfl_encoding_utf7 = {
	mbfl_no_encoding_utf7,
	"UTF-7",
	"UTF-7",
	mbfl_encoding_utf7_aliases,
	NULL,
	MBFL_ENCTYPE_GL_UNSAFE,
	&vtbl_utf7_wchar,
	&vtbl_wchar_utf7
};

const struct mbfl_convert_vtbl vtbl_utf7_wchar = {
	mbfl_no_encoding_utf7,
	mbfl_no_encoding_wchar,
	mbfl_filt_conv_common_ctor,
	NULL,
	mbfl_filt_conv_utf7_wchar,
	mbfl_filt_conv_utf7_wchar_flush,
	NULL,
};

const struct mbfl_convert_vtbl vtbl_wchar_utf7 = {
	mbfl_no_encoding_wchar,
	mbfl_no_encoding_utf7,
	mbfl_filt_conv_common_ctor,
	NULL,
	mbfl_filt_conv_wchar_utf7,
	mbfl_filt_conv_wchar_utf7_flush,
	NULL,
};


#define CK(statement)	do { if ((statement) < 0) return (-1); } while (0)

static unsigned int decode_base64_char(unsigned char c)
{
	if (c >= 'A' && c <= 'Z') {
		return c - 65;
	} else if (c >= 'a' && c <= 'z') {
		return c - 71;
	} else if (c >= '0' && c <= '9') {
		return c + 4;
	} else if (c == '+') {
		return 62;
	} else if (c == '/') {
		return 63;
	}
	return -1;
}

int mbfl_filt_conv_utf7_wchar(int c, mbfl_convert_filter *filter)
{
	int s, n = -1;

	if (filter->status) { /* Modified Base64 */
		n = decode_base64_char(c);
		if (n < 0) {
			if (filter->cache) {
				/* Either we were expecting the 2nd half of a surrogate pair which
				 * never came, or else the last Base64 data was not padded with zeroes */
				if (filter->cache & 0xfff0000) {
					(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
				} else {
					(*filter->output_function)(filter->cache | MBFL_WCSGROUP_THROUGH, filter->data);
				}
			}
			if (c == '-') {
				if (filter->status == 1) { /* "+-" -> "+" */
					CK((*filter->output_function)('+', filter->data));
				}
			} else if (c >= 0 && c < 0x80) { /* ASCII exclude '-' */
				CK((*filter->output_function)(c, filter->data));
			} else { /* illegal character */
				CK((*filter->output_function)(c | MBFL_WCSGROUP_THROUGH, filter->data));
			}
			filter->cache = filter->status = 0;
			return c;
		}
	}

	switch (filter->status) {
	/* directly encoded characters */
	case 0:
		if (c == '+') { /* '+' shift character */
			filter->status = 1;
		} else if (c >= 0 && c < 0x80) { /* ASCII */
			CK((*filter->output_function)(c, filter->data));
		} else { /* illegal character */
			CK((*filter->output_function)(c | MBFL_WCSGROUP_THROUGH, filter->data));
		}
		break;

	/* decode Modified Base64 */
	case 1:
	case 2:
		filter->cache |= n << 10;
		filter->status = 3;
		break;
	case 3:
		filter->cache |= n << 4;
		filter->status = 4;
		break;
	case 4:
		s = ((n >> 2) & 0xf) | (filter->cache & 0xffff);
		n = (n & 0x3) << 14;
		filter->status = 5;
		if (s >= 0xd800 && s < 0xdc00) {
			/* 1st part of surrogate pair */
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			s = (((s & 0x3ff) << 16) + 0x400000) | n;
			filter->cache = s;
		} else if (s >= 0xdc00 && s < 0xe000) {
			/* 2nd part of surrogate pair */
			if (filter->cache & 0xfff0000) {
				s &= 0x3ff;
				s |= (filter->cache & 0xfff0000) >> 6;
				filter->cache = n;
				if (s >= MBFL_WCSPLANE_SUPMIN && s < MBFL_WCSPLANE_SUPMAX) {
					CK((*filter->output_function)(s, filter->data));
				} else { /* illegal character */
					CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				}
			} else {
				CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				filter->cache = n;
			}
		} else {
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			filter->cache = n;
			CK((*filter->output_function)(s, filter->data));
		}
		break;

	case 5:
		filter->cache |= n << 8;
		filter->status = 6;
		break;
	case 6:
		filter->cache |= n << 2;
		filter->status = 7;
		break;
	case 7:
		s = ((n >> 4) & 0x3) | (filter->cache & 0xffff);
		n = (n & 0xf) << 12;
		filter->status = 8;
		if (s >= 0xd800 && s < 0xdc00) {
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			s = (((s & 0x3ff) << 16) + 0x400000) | n;
			filter->cache = s;
		} else if (s >= 0xdc00 && s < 0xe000) {
			/* 2nd part of surrogate pair */
			if (filter->cache & 0xfff0000) {
				s &= 0x3ff;
				s |= (filter->cache & 0xfff0000) >> 6;
				filter->cache = n;
				if (s >= MBFL_WCSPLANE_SUPMIN && s < MBFL_WCSPLANE_SUPMAX) {
					CK((*filter->output_function)(s, filter->data));
				} else { /* illegal character */
					CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				}
			} else {
				CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				filter->cache = n;
			}
		} else {
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			filter->cache = n;
			CK((*filter->output_function)(s, filter->data));
		}
		break;

	case 8:
		filter->cache |= n << 6;
		filter->status = 9;
		break;
	case 9:
		s = n | (filter->cache & 0xffff);
		filter->status = 2;
		if (s >= 0xd800 && s < 0xdc00) {
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			s = (((s & 0x3ff) << 16) + 0x400000);
			filter->cache = s;
		} else if (s >= 0xdc00 && s < 0xe000) {
			if (filter->cache & 0xfff0000) {
				s &= 0x3ff;
				s |= (filter->cache & 0xfff0000) >> 6;
				filter->cache = 0;
				if (s >= MBFL_WCSPLANE_SUPMIN && s < MBFL_WCSPLANE_SUPMAX) {
					CK((*filter->output_function)(s, filter->data));
				} else { /* illegal character */
					CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				}
			} else {
				CK((*filter->output_function)(s | MBFL_WCSGROUP_THROUGH, filter->data));
				filter->cache = 0;
			}
		} else {
			if (filter->cache & 0xfff0000) {
				/* We were waiting for the 2nd part of a surrogate pair */
				(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
			}
			filter->cache = 0;
			CK((*filter->output_function)(s, filter->data));
		}
		break;

	default:
		filter->status = 0;
		break;
	}

	return c;
}

static int mbfl_filt_conv_utf7_wchar_flush(mbfl_convert_filter *filter)
{
	if (filter->cache) {
		/* Either we were expecting the 2nd half of a surrogate pair which
		 * never came, or else the last Base64 data was not padded with zeroes */
		if (filter->cache & 0xfff0000) {
			(*filter->output_function)(0xD800 | (((filter->cache - 0x400000) >> 16) & 0x3FF) | MBFL_WCSGROUP_THROUGH, filter->data);
		} else {
			(*filter->output_function)(filter->cache | MBFL_WCSGROUP_THROUGH, filter->data);
		}
	}

	if (filter->flush_function) {
		(*filter->flush_function)(filter->data);
	}

	return 0;
}

int mbfl_filt_conv_wchar_utf7(int c, mbfl_convert_filter *filter)
{
	int s;

	int n = 0;
	if (c >= 0 && c < 0x80) { /* ASCII */
		if ((c >= 'A' && c <= 'Z') || (c >= 'a' && c <= 'z') || (c >= '0' && c <= '9') || c == '\0' || c == '/' || c == '-') {
			n = 1;
		} else if (c == ' ' || c == '\t' || c == '\r' || c == '\n' || c == '\'' || c == '(' || c == ')' || c == ',' || c == '.' || c == ':' || c == '?') {
			n = 2;
		}
	} else if (c >= 0 && c < MBFL_WCSPLANE_UCS2MAX) {
		;
	} else if (c >= MBFL_WCSPLANE_SUPMIN && c < MBFL_WCSPLANE_SUPMAX) {
		CK((*filter->filter_function)(((c >> 10) - 0x40) | 0xd800, filter));
		CK((*filter->filter_function)((c & 0x3ff) | 0xdc00, filter));
		return c;
	} else {
		CK(mbfl_filt_conv_illegal_output(c, filter));
		return c;
	}

	switch (filter->status) {
	case 0:
		if (n != 0) { /* directly encode characters */
			CK((*filter->output_function)(c, filter->data));
		} else { /* Modified Base64 */
			CK((*filter->output_function)('+', filter->data));
			filter->status = 1;
			filter->cache = c;
		}
		break;

	/* encode Modified Base64 */
	case 1:
		s = filter->cache;
		CK((*filter->output_function)(mbfl_base64_table[(s >> 10) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(s >> 4) & 0x3f], filter->data));
		if (n != 0) {
			CK((*filter->output_function)(mbfl_base64_table[(s << 2) & 0x3c], filter->data));
			if (n == 1) {
				CK((*filter->output_function)('-', filter->data));
			}
			CK((*filter->output_function)(c, filter->data));
			filter->status = 0;
		} else {
			filter->status = 2;
			filter->cache = ((s & 0xf) << 16) | c;
		}
		break;

	case 2:
		s = filter->cache;
		CK((*filter->output_function)(mbfl_base64_table[(s >> 14) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(s >> 8) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(s >> 2) & 0x3f], filter->data));
		if (n != 0) {
			CK((*filter->output_function)(mbfl_base64_table[(s << 4) & 0x30], filter->data));
			if (n == 1) {
				CK((*filter->output_function)('-', filter->data));
			}
			CK((*filter->output_function)(c, filter->data));
			filter->status = 0;
		} else {
			filter->status = 3;
			filter->cache = ((s & 0x3) << 16) | c;
		}
		break;

	case 3:
		s = filter->cache;
		CK((*filter->output_function)(mbfl_base64_table[(s >> 12) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(s >> 6) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[s & 0x3f], filter->data));
		if (n != 0) {
			if (n == 1) {
				CK((*filter->output_function)('-', filter->data));
			}
			CK((*filter->output_function)(c, filter->data));
			filter->status = 0;
		} else {
			filter->status = 1;
			filter->cache = c;
		}
		break;

	default:
		filter->status = 0;
		break;
	}

	return c;

}

int mbfl_filt_conv_wchar_utf7_flush(mbfl_convert_filter *filter)
{
	int status = filter->status;
	int cache = filter->cache;

	/* flush fragments */
	switch (status) {
	case 1:
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 10) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 4) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache << 2) & 0x3c], filter->data));
		CK((*filter->output_function)('-', filter->data));
		break;

	case 2:
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 14) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 8) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 2) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache << 4) & 0x30], filter->data));
		CK((*filter->output_function)('-', filter->data));
		break;

	case 3:
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 12) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[(cache >> 6) & 0x3f], filter->data));
		CK((*filter->output_function)(mbfl_base64_table[cache & 0x3f], filter->data));
		CK((*filter->output_function)('-', filter->data));
		break;
	}

	if (filter->flush_function) {
		(*filter->flush_function)(filter->data);
	}

	return 0;
}
