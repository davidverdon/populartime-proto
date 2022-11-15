<?php
namespace App\BusinessLogic\PopularTime;

/**
 * Class Curl
 *
 * @author David Verdon
 */
class Curl
{
    private $username = null ;
    private $password = null ;

    private $timeout = 0 ;
    private $connectionTimeout = 0 ;

    private $options = array(
        'echo'          => 0,
        'refresh'       => 1,
        'auto_close'    => 1,
    ) ;
    private $links = array() ;
    private $forms = array() ;

    private $fileTimers = array() ;
    private $speedTimers = array() ;

    private $tokens = array() ;
    private $handlers = array() ;
    private $processes = array() ;

    private static $userAgents = array(
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.67 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0.1',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2',
        'Opera/9.80 (Windows NT 5.1; U; en) Presto/2.10.229 Version/11.60',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
        'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; en-GB)',
        'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
    ) ;

    public function getUsername(): ?string
    {
        return $this->username ;
    }

    public function setUsername( string $username ): self
    {
        if( '' !== ( $username = trim( $username ) ) ) {
            $this->username = $username ;
        }
        return $this ;
    }

    public function getPassword(): ?string
    {
        return $this->password ;
    }

    public function setPassword( string $password ): self
    {
        if( '' !== ( $password = trim( $password ) ) ) {
            $this->password = $password ;
        }
        return $this ;
    }

    private function getLogin(): ?string
    {
        return ( $username = $this->getUsername() ) ? sprintf( '%s:%s', $username, $this->getPassword() ) : null ;
    }

    public function setTimeout( int $timeout = null ): self
    {
        $this->timeout = $timeout ;

        return $this ;
    }

    public function getTimeout(): int
    {
        return $this->timeout ;
    }

    public function setConnectionTimeout( int $connectionTimeout = null ): self
    {
        $this->connectionTimeout = $connectionTimeout ;

        return $this ;
    }

    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout ;
    }

    public function getEcho(): bool
    {
        return (bool)$this->getProperty('echo', $this->options);
    }

    public function setEcho( bool $echo ): self
    {
        $this->options[ 'echo' ] = $echo ? 1 : 0 ;

        return $this ;
    }

    public function getRefresh()
    {
        return (bool)$this->getProperty('refresh', $this->options);
    }

    public function setRefresh( bool $refresh )
    {
        $this->options[ 'refresh' ] = $refresh ? 1 : 0 ;

        return $this ;
    }

    public function isAutoClose(): bool
    {
        return (bool)$this->getProperty('auto_close', $this->options);
    }

    public function setAutoClose( bool $autoClose ): self
    {
        $this->options[ 'auto_close' ] = $autoClose ? 1 : 0 ;

        return $this ;
    }

    public function getLinks() : array
    {
        return $this->links ;
    }

    public function addLink( $url, $path = null ): ?string
    {
        if( '' !== ( $url = trim( $url ) ) ) {
            $link = array(
                'link' => $this->getNormalizedUrl( $url ),
                'path' => $path,
            ) ;
            $token = $this->getToken() ;
            $this->links[ $token ] = $link ;
        }
        return !empty( $token ) ? $token : null ;
    }

    public function hasLinks(): bool
    {
        return (bool)$this->links;
    }

    private function getFileTimers()
    {
        return $this->fileTimers ;
    }

    private function setFileTimers( array $fileTimers ): self
    {
        $this->fileTimers = $fileTimers ;

        return $this ;
    }

    private function addFileTimer( float $timer, int $fileCounter ): self
    {
        $timers = $this->getFileTimers() ;
        $timers[] = 60 * $fileCounter / $timer ;

        $timers = array_slice( $timers, -10 ) ;
        $this->setFileTimers( $timers ) ;

        return $this ;
    }

    private function getSpeedTimers()
    {
        return $this->speedTimers ;
    }

    private function setSpeedTimers( array $speedTimers ): self
    {
        $this->speedTimers = $speedTimers ;

        return $this ;
    }

    private function addSpeedTimer( float $timer, int $fileSize ): self
    {
        $timers = $this->getSpeedTimers() ;
        $timers[] = $fileSize / $timer ;

        $timers = array_slice( $timers, -10 ) ;
        $this->setSpeedTimers( $timers ) ;

        return $this ;
    }

    public function getToken(): string
    {
        do {
            $token = rand( 1000000000, 9999999999 ) ;
        }
        while( isset( $this->tokens[ $token ] ) ) ;

        $this->tokens[ $token ] = 1 ;

        return $token ;
    }

    public function getHandlers()
    {
        return $this->handlers ;
    }

    private function setHandlers( array $handlers ): self
    {
        $this->handlers = $handlers ;

        return $this ;
    }

    private function addHandler( $handler ): self
    {
        $this->handlers[] = $handler ;

        return $this ;
    }

    public function setProcesses( array $processes ): self
    {
        $this->processes = $processes ;

        return $this ;
    }

    public function getProcess( $token ): ?CurlProcess
    {
        return $this->processes[$token] ?? null ;
    }

    public function getProcessCounter(): int
    {
        return count( $this->processes ) ;
    }

    private function resetLinks(): self
    {
        $this->links = array() ;

        return $this ;
    }

    private function resetForms(): self
    {
        $this->forms = array() ;

        return $this ;
    }

    private function resetTokens(): self
    {
        $this->tokens = array() ;

        return $this ;
    }

    private function resetHandlers(): self
    {
        $this->setHandlers( array() ) ;

        return $this ;
    }

    private function resetProcesses(): self
    {
        $this->setProcesses( array() ) ;

        return $this ;
    }

    public function reset()
    {
        $this->closeHandlers() ;

        $this->resetLinks() ;
        $this->resetForms() ;
        $this->resetTokens() ;
        $this->resetHandlers() ;
        $this->resetProcesses() ;

        return $this ;
    }

    public function download(): int
    {
        $processes = array() ;
        $timer = $this->getTimer() ;

        if( $this->hasLinks() ) {
            foreach( $this->processLinks() as $token => &$process ) {
                $processes[ $token ] = $process ;
            }
        }
        $this->setProcesses( $processes ) ;
        $timer = $this->getTimer( $timer ) ;

        $this->addFileTimer( $timer, $this->getSuccessCounter( $processes ) ) ;
        $this->addSpeedTimer( $timer, $this->getDownloadFileSize( $processes ) ) ;

        return count( $processes ) ;
    }

    private function getSuccessCounter( array &$processes ): int
    {
        $counter = 0 ;

        foreach( $processes as $process ) {
            $counter += $process->isSuccess() ? 1 : 0 ;
        }
        return $counter ;
    }

    private function processLinks()
    {
        $this->resetHandlers() ;

        $links = $this->getLinks() ;
        $processes = $this->getLinkProcesses( $links ) ;

        foreach( $processes as $token => $process ) {
            $process->handleResult() ;

            if( !$process->hasError() ) {
                $this->storeProcessContent( $links[ $token ], $process ) ;
            }
        }
        if( $this->isAutoClose() ) {
            $this->closeHandlers() ;
        }
        return $processes ;
    }

    private function getDownloadFileSize( array &$processes ): int
    {
        $fileSize = 0 ;

        foreach( $processes as &$process ) {
            $fileSize += $this->getProcessFileSize( $process ) ;
        }
        return $fileSize ;
    }

    private function getProcessFileSize( CurlProcess $process ): int
    {
        $fileSize = 0 ;
        $success = $process->isSuccess() ;

        if( $success ) {
            $fileSize = strlen( $process->getContent() ) ;
        }
        return $fileSize ;
    }

    public function getSpeed(): float
    {
        $timers = $this->getSpeedTimers() ;
        $speed = $timers ? array_sum( $timers ) / count( $timers ) : 0 ;

        return $speed ;
    }

    public function getFileSpeed(): float
    {
        $timers = $this->getFileTimers() ;
        $speed = $timers ? array_sum( $timers ) / count( $timers ) : 0 ;

        return $speed ;
    }

    private function storeProcessContent( array &$record, CurlProcess $process ): self
    {
        $handler = $process->getCurlHandler() ;
        $content = curl_multi_getcontent( $handler ) ;

        if( empty( $record[ 'path' ] ) ) {
            $process->setContent( $content ) ;
        }
        else {
            $file = new DataFile( $record[ 'path' ] ) ;
            $file->write( $content ) ;

            $process->setFile( $file ) ;
        }
        return $this ;
    }

    private function getLinkProcesses( array $links )
    {
        $processes = $this->getNewProcesses( $links ) ;

        if( $processes ) {
            $this->loadProcesses( $processes ) ;
        }
        return $processes ;
    }

    private function getNewProcesses( array $links )
    {
        $processes = array() ;

        foreach( $links as $i => $link ) {
            $handler = $this->getCurlHandler( $link[ 'link' ] ) ;

            curl_setopt( $handler, CURLOPT_HEADER, 0 ) ;
            $processes[ $i ] = $this->getNewProcess( $link[ 'link' ], $handler ) ;
        }
        return $processes ;
    }

    private function getNewProcess( $link, $handler ): CurlProcess
    {
        return new CurlProcess( $link, $handler ) ;
    }

    private function loadProcesses( array $processes )
    {
        $handler = curl_multi_init() ;

        foreach( $processes as $process ) {
            curl_multi_add_handle( $handler, $process->getCurlHandler() ) ;
        }
        $this->executeHandler( $handler ) ;
        $this->closeProcesses( $processes, $handler ) ;

        return $this ;
    }

    private function getFormRequest( $action, array $input )
    {
        $values = $this->getInputValues( $input ) ;

        if( $values ) {
            $action .= FALSE === strpos( $action, '?' ) ? '?' : '&' ;
            $action .= $values ;
        }
        $action = $this->getNormalizedUrl( $action ) ;
        $handler = $this->getCurlHandler( $action ) ;

        $process = $this->getNewProcess( $action, $handler ) ;
        $process->execute() ;

        return $process ;
    }

    private function postFormRequest( $action, array $input )
    {
        $action = $this->getNormalizedUrl( $action ) ;
        $handler = $this->getCurlHandler( $action ) ;

        if( $input ) {
            $this->handleFormInput( $handler, $input ) ;
        }
        $process = $this->getNewProcess( $action, $handler ) ;
        $process->execute() ;

        return $process ;
    }

    private function handleFormInput( $handler, array $input = null )
    {
        if( $input ) {
            $values = $this->getInputValues( $input ) ;

            curl_setopt( $handler, CURLOPT_POST, count( $input ) ) ;
            curl_setopt( $handler, CURLOPT_POSTFIELDS, $values ) ;
        }
        return $handler ;
    }

    public function getInputValues( array $input ): string
    {
        foreach( $input as $key => &$value ) {
            $value = urlencode( trim( $value ) ) ;
            $value = sprintf( '%s=%s', $key, $value ) ;
        }
        return implode( '&', $input ) ;
    }

    private function getRandomUserAgent(): ?string
    {
        return ( $userAgents = self::$userAgents ) ? $userAgents[ array_rand( $userAgents ) ] : null ;
    }

    private function closeHandlers(): self
    {
        foreach( $this->getHandlers() as $handler ) {
            curl_close( $handler ) ;
        }
        $this->resetHandlers() ;

        return $this ;
    }

    private function closeProcesses( array $processes, $handler ): self
    {
        foreach( $processes as $process ) {
            $process->handleResult() ;
            $processHandler = $process->getCurlHandler() ;

            if( $processHandler ) {
                curl_multi_remove_handle( $handler, $processHandler ) ;
            }
        }
        curl_multi_close( $handler ) ;

        return $this ;
    }

    private function executeHandler( $handler )
    {
        $running = null ;

        do {
            curl_multi_exec( $handler, $running ) ;
        }
        while (
            $running
        ) ;
        return $handler ;
    }

    private function getTimer( $timer = null ): float
    {
        $current = microtime( true ) ;

        if( null === $timer ) {
            return $current ;
        }
        return $current - $timer ;
    }

    public function getLastModified( string $url ): ?string
    {
        $url = $this->getNormalizedUrl( $url ) ;

        if( $url ) {
            $handler = curl_init( $url ) ;

            curl_setopt( $handler, CURLOPT_RETURNTRANSFER, TRUE ) ;
            curl_setopt( $handler, CURLOPT_HEADER, TRUE ) ;
            curl_setopt( $handler, CURLOPT_NOBODY, TRUE ) ;
            curl_setopt( $handler, CURLOPT_TIMEOUT, 5 ) ;

            if( $header = curl_exec( $handler ) ) {
                $header = explode( "\n" , $header ) ;
                $parts = explode( 'Last-Modified: ' , $header[ 3 ] ) ;

                if( !empty( $parts[ 1 ] ) ) {
                    $lastModified = strtotime( $parts[ 1 ] ) ;
                }
            }
            curl_close( $handler ) ;
        }
        return !empty( $lastModified ) ? $lastModified : null ;
    }

    public function getFileSize( $url ): ?int
    {
        $url = $this->getNormalizedUrl( $url ) ;

        if( $url ) {
            $handler = curl_init( $url ) ;

            curl_setopt( $handler, CURLOPT_RETURNTRANSFER, TRUE ) ;
            curl_setopt( $handler, CURLOPT_HEADER, TRUE ) ;
            curl_setopt( $handler, CURLOPT_NOBODY, TRUE ) ;
            curl_setopt( $handler, CURLOPT_TIMEOUT, 5 ) ;

            curl_exec( $handler ) ;
            $size = curl_getinfo( $handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD ) ;

            curl_close( $handler ) ;

        }
        return isset( $size ) ? $size : null ;
    }

    private function getCurlHandler( $url )
    {
        $handler = curl_init() ;
        $this->addHandler( $handler ) ;

        curl_setopt( $handler, CURLOPT_URL, $url ) ; // Target URL
        curl_setopt( $handler, CURLOPT_REFERER, $url ) ;

        if( preg_match( '`^https://`i', $url ) ) {
            curl_setopt( $handler, CURLOPT_SSL_VERIFYPEER, FALSE ) ;
            curl_setopt( $handler, CURLOPT_SSL_VERIFYHOST, 0 ) ;
        }
        $this->initHandler( $handler ) ;

        return $handler ;
    }

    private function initHandler( $handler ): self
    {
        curl_setopt( $handler, CURLOPT_FOLLOWLOCATION, 1 ) ;
        curl_setopt( $handler, CURLOPT_HTTPPROXYTUNNEL, 0 ) ;
        curl_setopt( $handler, CURLOPT_RETURNTRANSFER, !$this->getEcho()) ;

        if( $timeout = $this->getTimeout() ) {
            curl_setopt( $handler, CURLOPT_TIMEOUT, $timeout ) ;
        }
        if( $connectionTimeout = $this->getConnectionTimeout() ) {
            curl_setopt( $handler, CURLOPT_CONNECTTIMEOUT, $connectionTimeout ) ;
        }
        if( $userAgent = $this->getRandomUserAgent() ) {
            curl_setopt( $handler, CURLOPT_USERAGENT, $userAgent ) ;
        }
        if( $this->getRefresh() ) {
            curl_setopt( $handler, CURLOPT_FRESH_CONNECT, TRUE ) ;
            curl_setopt( $handler, CURLOPT_FORBID_REUSE, TRUE ) ; // Turn "Keep Alive" off
        }
        if( $login = $this->getLogin() ) {
            curl_setopt( $handler, CURLOPT_HTTPAUTH, CURLAUTH_ANY ) ;
            curl_setopt( $handler, CURLOPT_USERPWD, $login ) ;
        }
        return $this ;
    }

    private function getNormalizedUrl( string $url ): string
    {
        $url = parse_url( $url ) ;

        if( !empty( $url[ 'path' ] ) ) {
            $path = &$url[ 'path' ] ;
            $parts = explode( '/', $path ) ;

            foreach( $parts as $i => $part ) {
                if( $part == urldecode( $part ) ) {
                    $parts[ $i ] = rawurlencode( $part ) ;
                }
            }
            $path = implode( '/', $parts ) ;
        }
        if( !empty( $url[ 'scheme' ] ) && !empty( $url[ 'host' ] ) ) {
            $link = sprintf( '%s://%s', $url[ 'scheme' ], $url[ 'host' ] ) ;
        }
        if( !empty( $url[ 'port' ] ) ) {
            $link .= ':'.$url[ 'port' ] ;
        }
        if( !empty( $url[ 'path' ] ) ) {
            $link .= $url[ 'path' ] ;
        }
        if( !empty( $url[ 'query' ] ) ) {
            $link .= '?'.$url[ 'query' ] ;
        }
        return str_replace( ' ', '%20', $link ) ;
    }

    private function getProperty( $key, $data, $default = null )
    {
        $value = $default ;

        if( is_array( $data ) ) {
            $value = $data[$key] ?? null;
        }
        if( is_object( $data ) ) {
            $value = $data->$key ?? null;
        }
        return $value ;
    }
}