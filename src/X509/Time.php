<?php

declare(strict_types=1);

namespace SmBc\X509;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1OctetString;
use SmBc\Asn1\ASN1Primitive;

/**
 * X.509 Time.
 * 
 * ```
 * Time ::= CHOICE {
 *     utcTime     UTCTime,
 *     generalTime GeneralizedTime
 * }
 * ```
 * 
 * Simplified implementation using OCTET STRING.
 */
class Time extends ASN1Object
{
    /** @var \DateTime The date/time */
    private \DateTime $time;

    /**
     * Constructor.
     * 
     * @param \DateTime $time The date/time
     */
    public function __construct(\DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * Get the DateTime.
     * 
     * @return \DateTime The time
     */
    public function getDateTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        // Simplified: encode as string
        // Real implementation should use UTCTime or GeneralizedTime
        $timeStr = $this->time->format('YmdHis') . 'Z';
        return new ASN1OctetString($timeStr);
    }

    /**
     * Create from ASN1 encodable.
     * 
     * @param ASN1Encodable $obj The object
     * @return self The time
     */
    public static function fromASN1(ASN1Encodable $obj): self
    {
        if ($obj instanceof ASN1OctetString) {
            $timeStr = $obj->getOctets();
            // Parse simplified format: YmdHisZ
            $year = substr($timeStr, 0, 4);
            $month = substr($timeStr, 4, 2);
            $day = substr($timeStr, 6, 2);
            $hour = substr($timeStr, 8, 2);
            $min = substr($timeStr, 10, 2);
            $sec = substr($timeStr, 12, 2);
            
            $dt = new \DateTime("$year-$month-$day $hour:$min:$sec", new \DateTimeZone('UTC'));
            return new self($dt);
        }
        
        throw new \InvalidArgumentException("Invalid Time object");
    }

    /**
     * Create from DateTime.
     * 
     * @param \DateTime $dt The DateTime
     * @return self The time
     */
    public static function fromDateTime(\DateTime $dt): self
    {
        return new self($dt);
    }
}
