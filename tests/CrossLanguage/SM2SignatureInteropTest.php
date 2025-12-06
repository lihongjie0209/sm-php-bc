<?php

declare(strict_types=1);

namespace SmBc\Tests\CrossLanguage;

use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\SM2;
use SmBc\Util\SecureRandom;

/**
 * SM2 Signature cross-language interoperability tests
 */
class SM2SignatureInteropTest extends BaseInteropTest
{
    private function generateTestKeyPair(): array
    {
        // Generate a fixed key pair for reproducible tests
        $keyPair = SM2::generateKeyPair();
        $privateKey = $keyPair->getPrivate();
        $publicKey = $keyPair->getPublic();
        $privateKeyHex = $keyPair->getPrivateKeyHex();
        
        return [$privateKey, $publicKey, $privateKeyHex];
    }
    
    /**
     * Test signing with PHP and verifying with both PHP and JavaScript
     */
    public function testSignWithPhpVerifyWithBoth(): void
    {
        if (!$this->isNodeJsAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }
        
        echo "\n=== Testing SM2 Signature: PHP sign, PHP+JS verify ===\n";
        
        [$privateKey, $publicKey, $privateKeyHex] = $this->generateTestKeyPair();
        
        $testMessages = [
            'Hello SM2!',
            'Test message',
            'SM2签名测试',
        ];
        
        foreach ($testMessages as $message) {
            echo "Testing message: \"{$message}\"\n";
            
            // Sign with PHP
            $signer = new SM2Signer();
            $signer->init(true, new ParametersWithRandom($privateKey, new SecureRandom()));
            $messageBytes = $message;
            $signer->updateBytes($messageBytes, 0, strlen($messageBytes));
            $signature = $signer->generateSignature();
            $signatureHex = $this->bytesToHex($signature);
            
            // Verify with PHP
            $verifier = new SM2Signer();
            $verifier->init(false, $publicKey);
            $verifier->updateBytes($messageBytes, 0, strlen($messageBytes));
            $phpValid = $verifier->verifySignature($signature);
            
            $this->assertTrue($phpValid, "PHP verification failed");
            
            // Get public key in uncompressed format
            $publicKeyHex = $this->getUncompressedPublicKey($publicKey);
            
            // Verify with JavaScript
            $jsValid = $this->verifyWithJavaScript($message, $signatureHex, $publicKeyHex);
            
            $this->assertTrue($jsValid, "JavaScript verification failed");
            
            echo "  ✓ Signature verified by both implementations\n";
        }
        
        echo "✓ All signature tests passed\n";
    }
    
    /**
     * Get uncompressed public key (04 + x + y)
     */
    private function getUncompressedPublicKey(ECPublicKeyParameters $publicKey): string
    {
        $Q = $publicKey->getQ();
        $affine = $Q->normalize();
        
        $x = $affine->getXCoord()->toBigInteger()->toString(16);
        $y = $affine->getYCoord()->toBigInteger()->toString(16);
        
        // Pad to 64 hex chars (32 bytes)
        $x = str_pad($x, 64, '0', STR_PAD_LEFT);
        $y = str_pad($y, 64, '0', STR_PAD_LEFT);
        
        return '04' . $x . $y;
    }
    
    /**
     * Verify signature using JavaScript
     */
    private function verifyWithJavaScript(string $message, string $signatureHex, string $publicKeyHex): bool
    {
        $escapedMessage = addcslashes($message, "\\\"\n\r\t");
        
        $script = $this->createJsScript(<<<JS
const message = new TextEncoder().encode("$escapedMessage");
const signature = new Uint8Array(
    "$signatureHex".match(/.{2}/g).map(byte => parseInt(byte, 16))
);
const publicKeyHex = "$publicKeyHex";

// Parse public key
const coordLen = (publicKeyHex.length - 2) / 2;
const xHex = publicKeyHex.substring(2, 2 + coordLen);
const yHex = publicKeyHex.substring(2 + coordLen);

const x = BigInt('0x' + xHex);
const y = BigInt('0x' + yHex);

const domainParams = smBc.SM2.getParameters();
const curve = domainParams.getCurve();
const Q = curve.createPoint(x, y);
const publicKey = new smBc.ECPublicKeyParameters(Q, domainParams);

const verifier = new smBc.SM2Signer();
verifier.init(false, publicKey);
verifier.update(message, 0, message.length);

const isValid = verifier.verifySignature(signature);

console.log(JSON.stringify({ isValid: isValid }));
JS
        );
        
        $result = $this->executeNodeJs($script);
        return $result['isValid'];
    }
}
