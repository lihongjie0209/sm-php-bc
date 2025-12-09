<?php

namespace SmBc\X509;

use SmBc\Asn1\ASN1Encodable;
use SmBc\Asn1\ASN1Object;
use SmBc\Asn1\ASN1ObjectIdentifier;
use SmBc\Asn1\ASN1Primitive;
use SmBc\Asn1\ASN1Sequence;
use SmBc\Asn1\DERSequence;

/**
 * X.509 Extensions
 * 
 * <pre>
 * Extensions  ::=  SEQUENCE SIZE (1..MAX) OF Extension
 * </pre>
 */
class X509Extensions extends ASN1Object
{
    /** @var X509Extension[] */
    private array $extensions = [];
    
    /** @var array<string, X509Extension> Extension OID to Extension map */
    private array $extensionMap = [];
    
    /**
     * @param X509Extension[] $extensions Array of extensions
     */
    public function __construct(array $extensions = [])
    {
        foreach ($extensions as $ext) {
            if (!($ext instanceof X509Extension)) {
                throw new \InvalidArgumentException('All elements must be X509Extension instances');
            }
            $this->addExtension($ext);
        }
    }
    
    /**
     * Create from ASN.1 sequence
     */
    public static function getInstance($obj): self
    {
        if ($obj instanceof self) {
            return $obj;
        }
        
        if ($obj instanceof ASN1Sequence) {
            $seq = $obj;
        } else if (is_string($obj)) {
            $seq = ASN1Sequence::getInstance(ASN1Primitive::fromByteArray($obj));
        } else {
            throw new \InvalidArgumentException('Unknown object in X509Extensions::getInstance');
        }
        
        $extensions = [];
        foreach ($seq->getObjects() as $obj) {
            $extensions[] = X509Extension::getInstance($obj);
        }
        
        return new self($extensions);
    }
    
    /**
     * Add an extension
     */
    public function addExtension(X509Extension $extension): void
    {
        $oid = $extension->getExtnId()->getId();
        $this->extensions[] = $extension;
        $this->extensionMap[$oid] = $extension;
    }
    
    /**
     * Get all extensions
     * 
     * @return X509Extension[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
    
    /**
     * Get extension by OID
     */
    public function getExtension(string $oid): ?X509Extension
    {
        return $this->extensionMap[$oid] ?? null;
    }
    
    /**
     * Check if extension exists
     */
    public function hasExtension(string $oid): bool
    {
        return isset($this->extensionMap[$oid]);
    }
    
    /**
     * Get all extension OIDs
     * 
     * @return string[]
     */
    public function getExtensionOIDs(): array
    {
        return array_keys($this->extensionMap);
    }
    
    /**
     * Get critical extension OIDs
     * 
     * @return string[]
     */
    public function getCriticalExtensionOIDs(): array
    {
        $critical = [];
        foreach ($this->extensions as $ext) {
            if ($ext->isCritical()) {
                $critical[] = $ext->getExtnId()->getId();
            }
        }
        return $critical;
    }
    
    /**
     * Get non-critical extension OIDs
     * 
     * @return string[]
     */
    public function getNonCriticalExtensionOIDs(): array
    {
        $nonCritical = [];
        foreach ($this->extensions as $ext) {
            if (!$ext->isCritical()) {
                $nonCritical[] = $ext->getExtnId()->getId();
            }
        }
        return $nonCritical;
    }
    
    public function toASN1Primitive(): ASN1Primitive
    {
        $v = [];
        foreach ($this->extensions as $ext) {
            $v[] = $ext->toASN1Primitive();
        }
        return new DERSequence($v);
    }
}
