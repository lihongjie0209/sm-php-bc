<?php

declare(strict_types=1);

namespace SmBc\Asn1;

/**
 * ASN.1 tag constants
 * 
 * Based on: org.bouncycastle.asn1.BERTags
 *           sm-js-bc/src/asn1/ASN1Tags.ts
 */
class ASN1Tags
{
    public const BOOLEAN = 0x01;
    public const INTEGER = 0x02;
    public const BIT_STRING = 0x03;
    public const OCTET_STRING = 0x04;
    public const NULL = 0x05;
    public const OBJECT_IDENTIFIER = 0x06;
    public const EXTERNAL = 0x08;
    public const ENUMERATED = 0x0a;
    public const SEQUENCE = 0x10;
    public const SEQUENCE_OF = 0x10;
    public const SET = 0x11;
    public const SET_OF = 0x11;

    public const UTF8_STRING = 0x0c;
    public const PRINTABLE_STRING = 0x13;
    public const T61_STRING = 0x14;
    public const IA5_STRING = 0x16;
    public const UTC_TIME = 0x17;
    public const GENERALIZED_TIME = 0x18;
    public const GRAPHIC_STRING = 0x19;
    public const VISIBLE_STRING = 0x1a;
    public const GENERAL_STRING = 0x1b;
    public const UNIVERSAL_STRING = 0x1c;
    public const BMP_STRING = 0x1e;

    public const CONSTRUCTED = 0x20;
    public const APPLICATION = 0x40;
    public const TAGGED = 0x80;
}
