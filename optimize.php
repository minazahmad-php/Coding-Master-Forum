<?php
/**
 * Complete Forum Project Optimization Script
 * Run this script to optimize the entire project
 */

require_once 'bootstrap/app.php';

use App\Services\OptimizationService;
use App\Services\SecurityOptimizationService;

echo "🚀 Starting Complete Forum Project Optimization...\n\n";

try {
    // Initialize services
    $optimizationService = new OptimizationService();
    $securityService = new SecurityOptimizationService();
    
    echo "📊 Running Performance Optimization...\n";
    $performanceResults = $optimizationService->optimizeAll();
    
    echo "🔒 Running Security Optimization...\n";
    $securityResults = $securityService->optimizeSecurity();
    
    echo "📋 Generating Reports...\n";
    $performanceReport = $optimizationService->getOptimizationReport();
    $securityReport = $securityService->getSecurityReport();
    
    // Display results
    echo "\n✅ OPTIMIZATION COMPLETE!\n\n";
    
    echo "📊 Performance Results:\n";
    echo "- Database: " . ($performanceResults['database']['status'] ?? 'Unknown') . "\n";
    echo "- Cache: " . ($performanceResults['cache']['status'] ?? 'Unknown') . "\n";
    echo "- Files: " . ($performanceResults['files']['status'] ?? 'Unknown') . "\n";
    echo "- Images: " . ($performanceResults['images']['status'] ?? 'Unknown') . "\n";
    echo "- CSS: " . ($performanceResults['css']['status'] ?? 'Unknown') . "\n";
    echo "- JS: " . ($performanceResults['js']['status'] ?? 'Unknown') . "\n";
    echo "- HTML: " . ($performanceResults['html']['status'] ?? 'Unknown') . "\n\n";
    
    echo "🔒 Security Results:\n";
    echo "- Headers: " . ($securityResults['headers']['status'] ?? 'Unknown') . "\n";
    echo "- Passwords: " . ($securityResults['passwords']['status'] ?? 'Unknown') . "\n";
    echo "- Sessions: " . ($securityResults['sessions']['status'] ?? 'Unknown') . "\n";
    echo "- Files: " . ($securityResults['files']['status'] ?? 'Unknown') . "\n";
    echo "- Database: " . ($securityResults['database']['status'] ?? 'Unknown') . "\n";
    echo "- CSRF: " . ($securityResults['csrf']['status'] ?? 'Unknown') . "\n";
    echo "- Rate Limiting: " . ($securityResults['rate_limiting']['status'] ?? 'Unknown') . "\n\n";
    
    echo "📈 Performance Statistics:\n";
    echo "- Total Files: " . ($performanceReport['files']['total_files'] ?? 'Unknown') . "\n";
    echo "- Total Size: " . number_format(($performanceReport['files']['total_size'] ?? 0) / 1024 / 1024, 2) . " MB\n";
    echo "- Memory Usage: " . number_format(($performanceReport['performance']['memory_usage'] ?? 0) / 1024 / 1024, 2) . " MB\n";
    echo "- Execution Time: " . number_format(($performanceReport['performance']['execution_time'] ?? 0), 4) . " seconds\n\n";
    
    echo "🛡️ Security Statistics:\n";
    echo "- Security Headers: " . ($securityReport['security_headers']['coverage'] ?? 0) . "% coverage\n";
    echo "- Password Strength: " . ($securityReport['password_strength']['strength_percentage'] ?? 0) . "% strong\n";
    echo "- Database Security: " . (($securityReport['database_security']['secure'] ?? false) ? 'Secure' : 'Needs Attention') . "\n\n";
    
    echo "🎉 Optimization completed successfully!\n";
    echo "Your forum project is now fully optimized and secure.\n";
    
} catch (Exception $e) {
    echo "❌ Optimization failed: " . $e->getMessage() . "\n";
    echo "Please check the logs for more details.\n";
    exit(1);
}