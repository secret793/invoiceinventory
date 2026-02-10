<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Block suspicious request patterns
        $this->blockSuspiciousRequests($request);
        
        // 2. Block malicious user agents
        $this->blockMaliciousUserAgents($request);
        
        // 3. Validate file uploads
        $this->validateFileUploads($request);
        
        // 4. Block SQL injection attempts
        $this->blockSqlInjection($request);
        
        // 5. Block XSS attempts
        $this->blockXss($request);
        
        // 6. Rate limiting (basic)
        $this->rateLimit($request);
        
        // 7. Log suspicious activity
        $this->logSuspiciousActivity($request);
        
        return $next($request);
    }
    
    /**
     * Block suspicious request patterns
     */
    private function blockSuspiciousRequests(Request $request): void
    {
        $uri = $request->getRequestUri();
        
        // Block WordPress-related paths (shouldn't exist in Laravel)
        $wpPaths = [
            '/wp-admin',
            '/wp-content',
            '/wp-includes',
            '/wp-login',
            '/wp-config',
            '/wp-cron',
            '/xmlrpc.php',
            '/readme.html',
            '/license.txt',
        ];
        
        foreach ($wpPaths as $path) {
            if (str_contains($uri, $path)) {
                Log::warning('WordPress path access attempt blocked', [
                    'ip' => $request->ip(),
                    'path' => $uri,
                    'user_agent' => $request->userAgent(),
                ]);
                
                abort(404, 'Not Found');
            }
        }
        
        // Block common exploit paths
        $exploitPaths = [
            '/.env',
            '/.git',
            '/phpinfo',
            '/shell',
            '/backdoor',
            '/c99',
            '/r57',
            '/config.php',
            '/.htaccess',
            '/../',
            '/..%2F',
            '%00',
        ];
        
        foreach ($exploitPaths as $path) {
            if (str_contains($uri, $path)) {
                Log::critical('Exploit path access attempt blocked', [
                    'ip' => $request->ip(),
                    'path' => $uri,
                    'user_agent' => $request->userAgent(),
                ]);
                
                abort(404, 'Not Found');
            }
        }
    }
    
    /**
     * Block malicious user agents
     */
    private function blockMaliciousUserAgents(Request $request): void
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $maliciousAgents = [
            'nikto',
            'sqlmap',
            'nmap',
            'masscan',
            'nessus',
            'acunetix',
            'havij',
            'dirbuster',
            'metis',
            'zmeu',
        ];
        
        foreach ($maliciousAgents as $agent) {
            if (str_contains($userAgent, $agent)) {
                Log::critical('Malicious user agent blocked', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                ]);
                
                abort(403, 'Forbidden');
            }
        }
    }
    
    /**
     * Validate file uploads
     */
    private function validateFileUploads(Request $request): void
    {
        if (!$request->hasFile(null)) {
            return;
        }
        
        foreach ($request->allFiles() as $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    $this->checkFile($f, $request);
                }
            } else {
                $this->checkFile($file, $request);
            }
        }
    }
    
    /**
     * Check individual file
     */
    private function checkFile($file, Request $request): void
    {
        // Block dangerous extensions
        $dangerousExtensions = [
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
            'exe', 'sh', 'bat', 'cmd', 'com',
            'htaccess', 'htpasswd',
        ];
        
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $dangerousExtensions)) {
            Log::critical('Dangerous file upload attempt blocked', [
                'ip' => $request->ip(),
                'filename' => $file->getClientOriginalName(),
                'extension' => $extension,
            ]);
            
            abort(403, 'Forbidden file type');
        }
        
        // Check for double extensions
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.php\./i', $filename)) {
            Log::critical('Double extension file upload blocked', [
                'ip' => $request->ip(),
                'filename' => $filename,
            ]);
            
            abort(403, 'Invalid filename');
        }
        
        // Scan file content for malicious patterns
        $content = file_get_contents($file->getRealPath());
        $maliciousPatterns = [
            '<?php',
            '<%',
            '<script',
            'eval(',
            'base64_decode',
            'exec(',
            'system(',
            'shell_exec(',
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                Log::critical('Malicious content in upload detected', [
                    'ip' => $request->ip(),
                    'filename' => $filename,
                    'pattern' => $pattern,
                ]);
                
                abort(403, 'Malicious content detected');
            }
        }
    }
    
    /**
     * Block SQL injection attempts
     */
    private function blockSqlInjection(Request $request): void
    {
        $inputs = array_merge(
            $request->query->all(),
            $request->request->all()
        );
        
        $sqlPatterns = [
            '/union.*select/i',
            '/select.*from/i',
            '/insert.*into/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/update.*set/i',
            '/--/i',
            '/;.*--/i',
            '/\/\*/i',
        ];
        
        foreach ($inputs as $key => $value) {
            if (!is_string($value)) continue;
            
            foreach ($sqlPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    Log::warning('SQL injection attempt blocked', [
                        'ip' => $request->ip(),
                        'parameter' => $key,
                        'value' => substr($value, 0, 100),
                    ]);
                    
                    abort(403, 'Invalid input');
                }
            }
        }
    }
    
    /**
     * Block XSS attempts
     */
    private function blockXss(Request $request): void
    {
        $inputs = array_merge(
            $request->query->all(),
            $request->request->all()
        );
        
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/onerror=/i',
            '/onload=/i',
            '/onclick=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
        ];
        
        foreach ($inputs as $key => $value) {
            if (!is_string($value)) continue;
            
            foreach ($xssPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    Log::warning('XSS attempt blocked', [
                        'ip' => $request->ip(),
                        'parameter' => $key,
                        'value' => substr($value, 0, 100),
                    ]);
                    
                    abort(403, 'Invalid input');
                }
            }
        }
    }
    
    /**
     * Basic rate limiting
     */
    private function rateLimit(Request $request): void
    {
        $key = 'rate_limit:' . $request->ip();
        $maxAttempts = 100; // requests
        $decayMinutes = 1; // minute
        
        if (cache()->has($key)) {
            $attempts = cache()->get($key);
            
            if ($attempts >= $maxAttempts) {
                Log::warning('Rate limit exceeded', [
                    'ip' => $request->ip(),
                    'attempts' => $attempts,
                ]);
                
                abort(429, 'Too Many Requests');
            }
            
            cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        } else {
            cache()->put($key, 1, now()->addMinutes($decayMinutes));
        }
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request): void
    {
        // Log requests to sensitive paths
        $sensitivePaths = ['/admin', '/api'];
        $uri = $request->getRequestUri();
        
        foreach ($sensitivePaths as $path) {
            if (str_starts_with($uri, $path)) {
                Log::info('Sensitive path access', [
                    'ip' => $request->ip(),
                    'path' => $uri,
                    'user_agent' => $request->userAgent(),
                    'method' => $request->method(),
                ]);
                break;
            }
        }
    }
}
