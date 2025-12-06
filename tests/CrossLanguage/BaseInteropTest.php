<?php

declare(strict_types=1);

namespace SmBc\Tests\CrossLanguage;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base class for cross-language interoperability tests
 * Tests PHP implementation against JavaScript sm-js-bc via Node.js
 */
abstract class BaseInteropTest extends TestCase
{
    /**
     * Execute a Node.js script and return the result
     * 
     * @param string $script JavaScript code to execute
     * @return array Decoded JSON result
     * @throws RuntimeException If Node.js execution fails
     */
    protected function executeNodeJs(string $script): array
    {
        // Create temporary script file in project root so it can access node_modules
        $projectRoot = dirname(__DIR__, 2);
        $scriptFile = $projectRoot . '/temp_test_' . uniqid() . '.mjs';
        
        try {
            file_put_contents($scriptFile, $script);
            
            // Execute Node.js from project directory
            $command = sprintf('cd %s && node %s 2>&1', 
                escapeshellarg($projectRoot),
                escapeshellarg(basename($scriptFile))
            );
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $errorOutput = implode("\n", $output);
                throw new RuntimeException("Node.js execution failed: {$errorOutput}");
            }
            
            $jsonOutput = implode("\n", $output);
            $result = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Failed to parse JSON output: " . json_last_error_msg() . "\nOutput: {$jsonOutput}");
            }
            
            if (isset($result['error'])) {
                throw new RuntimeException("JavaScript error: {$result['error']}");
            }
            
            return $result;
            
        } finally {
            // Clean up
            if (file_exists($scriptFile)) {
                unlink($scriptFile);
            }
        }
    }
    
    /**
     * Check if Node.js is available
     * 
     * @return bool
     */
    protected function isNodeJsAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('node --version 2>&1', $output, $returnCode);
        
        return $returnCode === 0;
    }
    
    /**
     * Convert byte array to hex string
     * 
     * @param string $bytes
     * @return string
     */
    protected function bytesToHex(string $bytes): string
    {
        return bin2hex($bytes);
    }
    
    /**
     * Convert hex string to byte array
     * 
     * @param string $hex
     * @return string
     */
    protected function hexToBytes(string $hex): string
    {
        if (strlen($hex) % 2 !== 0) {
            throw new \InvalidArgumentException('Hex string must have even length');
        }
        
        return hex2bin($hex);
    }
    
    /**
     * Create a JavaScript script that uses sm-js-bc npm package
     * 
     * @param string $code JavaScript code (can use smBc as the imported module)
     * @return string Complete JavaScript module code
     */
    protected function createJsScript(string $code): string
    {
        return <<<JS
import * as smBc from 'sm-js-bc';

try {
    $code
} catch (error) {
    console.log(JSON.stringify({ error: error.message, stack: error.stack }));
    process.exit(1);
}
JS;
    }
}
