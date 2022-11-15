<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\BusinessLogic\PopularTime\PopularTimeManager;

class PopularTimeController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function indexAction( Request $request ): Response
    {
        $q = trim( (string) $request->get( 'q' ) ) ;
        $popularTime = $this->getPopularTime( $q ) ;

        if( !$q ) {
            $q = 'Centre commercial du Pont Neuf, Rue de la Gare 25, 1110 Morges, Suisse' ;
        }
        return $this->render('PopularTime/index.html.twig', [
            'q' => $q,
            'popularTime' => $popularTime,
        ]);
    }

    private function getPopularTime( string $location ): ?array
    {
        $popularTime = null ;

        if( $location = trim( $location ) ) {
            $manager = new PopularTimeManager() ;
            $popularTime = $manager->getPlace( $location ) ;
        }
        return $popularTime ;
    }
}
