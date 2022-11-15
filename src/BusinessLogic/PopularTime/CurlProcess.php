<?php
namespace App\BusinessLogic\PopularTime;

/**
 * Class CurlProcess
 *
 * @author David Verdon
 */
class CurlProcess
{
    protected $link ;
    protected $curlHandler ;

    protected $infos = array() ;

    protected $errorMessage = null ;
    protected $content = null ;

    protected static $httpErrorMessages = array(
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302	=> 'Found',
        303	=> 'See Other',
        304	=> 'Not Modified',
        305	=> 'Use Proxy',
        307	=> 'Temporary Redirect',
        308	=> 'Permanent Redirect',
        310	=> 'Too many Redirects',

        400 => 'Bad Request',
        401 => 'Unautorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410	=> 'Gone',
        411	=> 'Length',
        412	=> 'Precondition Failed',
        413	=> 'Request Entity Too Large',
        414	=> 'Request-URI Too Long',
        415	=> 'Unsupported Media Type',
        416	=> 'Requested range unsatisfiable',
        417	=> 'Expectation failed',
        418	=> 'I\'m a teapot',
        421	=> 'Bad mapping / Misdirected Request',
        422	=> 'Unprocessable entity',
        423	=> 'Locked',
        424	=> 'Method failure',
        425	=> 'Unordered Collection',
        426	=> 'Upgrade Required',
        428	=> 'Precondition Required',
        429	=> 'Too Many Requests',
        431	=> 'Request Header Fields Too Large',
        449	=> 'Retry With',
        450	=> 'Blocked by Windows Parental Controls',
        451	=> 'Unavailable For Legal Reasons',
        456	=> 'Unrecoverable Error',

        444	=> 'No Response',
        495	=> 'SSL Certificate Error',
        496	=> 'SSL Certificate Required',
        497	=> 'HTTP Request',
        498	=> 'Token expired/invalid',
        499	=> 'Client Closed Request',

        500	=> 'Internal Server Error',
        501	=> 'Not Implemented',
        502	=> 'Bad Gateway or Proxy Error',
        503	=> 'Service Unavailable',
        504	=> 'Gateway Time-out',
        505	=> 'HTTP Version not supported',
        506	=> 'Variant Also Negotiates',
        507	=> 'Insufficient storage',
        508	=> 'Loop detected',
        509	=> 'Bandwidth Limit Exceeded',
        510	=> 'Not extended',
        511	=> 'Network authentication required',

        520	=> 'Unknown Error',
        521	=> 'Web Server Is Down',
        522	=> 'Connection Timed Out',
        523	=> 'Origin Is Unreachable',
        524	=> 'A Timeout Occurred',
        525	=> 'SSL Handshake Failed',
        526	=> 'Invalid SSL Certificate',
        527	=> 'Railgun Error',
    ) ;

    public function __construct( $link, $curlHandler )
    {
        $this->setLink( $link ) ;
        $this->setCurlHandler( $curlHandler ) ;
    }

    protected function getHttpErrorMessage( $httpCode ): ?string
    {
        if( $httpCode = (int) $httpCode ) {
            if( isset( self::$httpErrorMessages[ $httpCode ] ) ) {
                return self::$httpErrorMessages[ $httpCode ] ;
            }
        }
        return null ;
    }

    public function getLink(): string
    {
        if( !$link = trim( $this->link ) ) {
            throw new \DomainException( 'No url defined!' ) ;
        }
        return $link ;
    }

    public function setLink( $link ): self
    {
        if( $link = trim( $link ) ) {
            $this->link = $link ;
        }
        return $this ;
    }

    public function getCurlHandler()
    {
        return $this->curlHandler ;
    }

    public function setCurlHandler( $curlHandler ): self
    {
        $this->curlHandler = $curlHandler ;

        return $this ;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage ;
    }

    public function setErrorMessage( $errorMessage )
    {
        $this->errorMessage = $errorMessage ;

        return $this ;
    }

    public function getInfos(): array
    {
        return $this->infos ;
    }

    public function setInfos( array $infos ): self
    {
        foreach( $infos as $key => $value ) {
            $this->setInfo( $key, $value ) ;
        }
        return $this ;
    }

    public function getInfo( $key )
    {
        if( !isset( $this->infos[ $key ] ) ) {
            THROW NEW \DomainException( sprintf( 'Unknown info %s', $key ) ) ;
        }
        return $this->infos[ $key ] ;
    }

    public function setInfo( $key, $value ): self
    {
        $this->infos[ $key ] = $value ;

        return $this ;
    }

    public function timeout(): bool
    {
        return false !== strpos( $this->getErrorMessage(), 'Operation timed out' );
    }

    public function isPartial(): bool
    {
        $error = $this->getErrorMessage() ;

        $messages = array(
            'Operation timed out',
            'bytes received',
        ) ;
        foreach( $messages as $message ) {
            if( false === strpos( $error, $message ) ) {
                return false ;
            }
        }
        return true ;
    }

    public function isRefused(): bool
    {
        if( !$this->getHttpCode() ) {
            $errors = array(
                'Failed to connect',
                'Connection time-out',
                'Connection timed out',
            ) ;
            $errorMessage = $this->getErrorMessage() ;

            foreach( $errors as $error ) {
                if( false !== strpos( $errorMessage, $error ) ) {
                    return true ;
                }
            }
        }
        return false ;
    }

    public function isUnavailable(): bool
    {
        $code = (int) $this->getHttpCode() ;

        return 503 == $code ;
    }

    public function isForbidden(): bool
    {
        $code = (int) $this->getHttpCode() ;

        return 403 == $code ;
    }

    public function isUnresolved(): bool
    {
        return !$this->getHttpCode() && false !== strpos( $this->getErrorMessage(), 'Couldn\'t resolve host' );
    }

    public function isDown(): bool
    {
        return !$this->getHttpCode() && false !== strpos( $this->getErrorMessage(), 'Recv failure: Connection reset by peer' );
    }

    public function getContent( $format = null, bool $toArray = false ): ?string
    {
        $content = trim( $this->content ) ;

        if( $content ) {
            switch( strtolower( $format ) ) {
                case 'json':
                    $content = ( $json = json_decode( $content ) ) ? $json : $content ;
                    break ;

                case 'xml':
                    $content = ( $xml = simplexml_load_string( $content ) ) ? $xml : $content ;
                    break ;
            }
            if( $toArray ) {
                $content = $this->getStdClassToArray( $content ) ;
            }
        }
        return $content ;
    }

    private function getStdClassToArray( $class ): array
    {
        if( is_object( $class ) && 'stdClass' == get_class( $class ) ) {
            $class = (array) $class ;
        }
        if( is_array( $class ) ) {
            foreach( $class as $property => &$value ) {
                if( is_array( $value ) || ( is_object( $value ) && 'stdClass' == get_class( $value ) ) ) {
                    $value = $this->getStdClassToArray( $value ) ;
                }
            }
        }
        return $class ;
    }

    public function setContent( $content ): self
    {
        $this->content = $content ;

        return $this ;
    }

    public function getIpAddress(): string
    {
        return $this->getInfo( 'local_ip' ) ;
    }

    public function getHttpCode(): string
    {
        return $this->getInfo( 'http_code' ) ;
    }

    public function hasError(): bool
    {
        return 200 != $this->getHttpCode() || $this->getErrorMessage() ;
    }

    public function isSuccess(): bool
    {
        return !$this->hasError() ;
    }

    public function execute(): self
    {
        $content = curl_exec( $this->getCurlHandler() ) ;
        $this->handleResult() ;

        if( !$this->getErrorMessage() ) {
            $this->setContent( $content ) ;
        }
        return $this ;
    }

    public function handleResult()
    {
        $handler = $this->getCurlHandler() ;

        $infos = curl_getinfo( $handler ) ;
        $errorMessage = curl_error( $handler ) ;

        if( $infos ) {
            $this->setInfos( $infos )  ;
        }
        if( !$errorMessage ) {
            $errorMessage = $this->getHttpErrorMessage( $this->getHttpCode() ) ;
        }
        if( $errorMessage ) {
            $this->setErrorMessage( $errorMessage ) ;
        }
        return $this ;
    }

    public function close(): self
    {
        if( $handler = &$this->curlHandler ) {
            curl_close( $handler ) ;
            $handler = null ;
        }
        return $this ;
    }
}