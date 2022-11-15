<?php
namespace App\BusinessLogic\PopularTime;

class PopularTimeManager
{
    const PARAMS = [
        'tbm' => 'map',
        'hl' => 'en',
        'pb' => '!4m12!1m3!1d4005.9771522653964!2d-122.42072974863942!3d37.8077459796541!2m3!1f0!2f0!3f0!3m2!1i1125!2i976!4f13.1!7i20!10b1!12m6!2m3!5m1!6e2!20e3!10b1!16b1!19m3!2m2!1i392!2i106!20m61!2m2!1i203!2i100!3m2!2i4!5b1!6m6!1m2!1i86!2i86!1m2!1i408!2i200!7m46!1m3!1e1!2b0!3e3!1m3!1e2!2b1!3e2!1m3!1e2!2b0!3e3!1m3!1e3!2b0!3e3!1m3!1e4!2b0!3e3!1m3!1e8!2b0!3e3!1m3!1e3!2b1!3e2!1m3!1e9!2b1!3e2!1m3!1e10!2b0!3e3!1m3!1e10!2b1!3e2!1m3!1e10!2b0!3e4!2b1!4b1!9b0!22m6!1sa9fVWea_MsX8adX8j8AE%3A1!2zMWk6Mix0OjExODg3LGU6MSxwOmE5ZlZXZWFfTXNYOGFkWDhqOEFFOjE!7e81!12e3!17sa9fVWea_MsX8adX8j8AE%3A564!18e15!24m15!2b1!5m4!2b1!3b1!5b1!6b1!10m1!8e3!17b1!24b1!25b1!26b1!30m1!2b1!36b1!26m3!2m2!1i80!2i92!30m28!1m6!1m2!1i0!2i0!2m2!1i458!2i976!1m6!1m2!1i1075!2i0!2m2!1i1125!2i976!1m6!1m2!1i0!2i0!2m2!1i1125!2i20!1m6!1m2!1i0!2i956!2m2!1i1125!2i976!37m1!1e81!42b1!47m0!49m1!3b1',
    ] ;
    const MAP_DAYS = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday',
    ];

    public function getPlace( string $location ): ?array
    {
        $result = $this->searchLocation( $location ) ;

        if( !empty( $result[ 0 ][ 1 ][ 0 ][ 14 ] ) ) {
            return $this->getPopularTimeData( $result[ 0 ][ 1 ][ 0 ][ 14 ] ) ;
        }
        return null ;
    }

    private function getPopularTimeData( array $popularTime ): array
    {
        $data = [];

        if( isset( $popularTime[84][0] ) ) {
            $data[ 'popular_time' ] = $this->mapDaysOnPopularTime( $popularTime[84][0] );
        }
        if( isset( $popularTime[84][6] ) ) {
            $data[ 'now' ] = $popularTime[84][6] ;
        }
        if( isset( $popularTime[117][0] ) ) {
            $data[ 'time_spent' ] = $popularTime[117][0] ;
        }
        return $data ;
    }

    private function mapDaysOnPopularTime( array $popularTimes ): array
    {
        $popularTimeDays = [] ;

        foreach( $popularTimes as $popularTime ) {
            $day = self::MAP_DAYS[ $popularTime[ 0 ] ] ;
            $popularTimeDays[ $day ] = $this->getPopularTimeHours( $popularTime ) ;
        }
        return $popularTimeDays;
    }

    private function getPopularTimeHours( array $popularTime ): array
    {
        $hours = [] ;

        if( !empty( $popularTime[ 1 ] ) && is_array( $popularTime[ 1 ] ) ) {
            foreach( $popularTime[ 1 ] as $record ) {
                $percent = $record[ 1 ] ;
                $info = $record[ 2 ] ;
                $hour = $record[ 0 ] ;
                $prettyHour = ( $hour < 10 ? '0'.$hour : $hour ).':00' ;

                if( !$info && $percent == 0 ) {
                    $info = 'Usually not frequented' ;
                }
                $hours[ $hour ] = [
                    'hour' => $prettyHour,
                    'percent' => $percent,
                    'info' => $info,
                ] ;
            }
        }
        return $hours ;
    }

    private function searchLocation( string $location ): ?array
    {
        $url = 'https://www.google.com/search?'.http_build_query( self::PARAMS )."&q=$location" ; //." $placeName"

        $curl = new Curl() ;
        $token = $curl->addLink( $url ) ;

        $curl->download() ;

        $process = $curl->getProcess($token) ;
        $content = $process->getContent() ;

        if( $content ) {
            $content = str_replace(
                ")]}'", '', $content
            ) ;
            return json_decode( $content, true ) ;
        }
        return null ;
    }
}