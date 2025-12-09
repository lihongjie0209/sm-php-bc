<?php

declare(strict_types=1);

namespace SmBc\X509;

use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * X.509 Validity period.
 * 
 * ```
 * Validity ::= SEQUENCE {
 *     notBefore Time,
 *     notAfter  Time
 * }
 * ```
 */
class Validity extends ASN1Object
{
    /** @var Time The not before time */
    private Time $notBefore;
    
    /** @var Time The not after time */
    private Time $notAfter;

    /**
     * Constructor.
     * 
     * @param Time $notBefore The not before time
     * @param Time $notAfter The not after time
     */
    public function __construct(Time $notBefore, Time $notAfter)
    {
        $this->notBefore = $notBefore;
        $this->notAfter = $notAfter;
    }

    /**
     * Get the not before time.
     * 
     * @return Time The not before time
     */
    public function getNotBefore(): Time
    {
        return $this->notBefore;
    }

    /**
     * Get the not after time.
     * 
     * @return Time The not after time
     */
    public function getNotAfter(): Time
    {
        return $this->notAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function toASN1Primitive(): ASN1Primitive
    {
        return new DERSequence([
            $this->notBefore->toASN1Primitive(),
            $this->notAfter->toASN1Primitive()
        ]);
    }

    /**
     * Create from ASN1Sequence.
     * 
     * @param ASN1Sequence $seq The sequence
     * @return self The validity
     */
    public static function fromSequence(ASN1Sequence $seq): self
    {
        if ($seq->size() !== 2) {
            throw new \InvalidArgumentException("Invalid Validity sequence");
        }
        
        $notBefore = Time::fromASN1($seq->getObjectAt(0));
        $notAfter = Time::fromASN1($seq->getObjectAt(1));
        
        return new self($notBefore, $notAfter);
    }

    /**
     * Create a Validity from DateTime objects.
     * 
     * @param \DateTime $notBefore The not before date
     * @param \DateTime $notAfter The not after date
     * @return self The validity
     */
    public static function fromDateTimes(\DateTime $notBefore, \DateTime $notAfter): self
    {
        return new self(
            Time::fromDateTime($notBefore),
            Time::fromDateTime($notAfter)
        );
    }
}
