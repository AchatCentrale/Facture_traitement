<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/pdf", name="pdf_base")
 */
class downloadPdf
{
    /**
     * @Route("/", name="base_list")
     */
    public function pdf_list()
    {

        return new Response(
            '<html><body>Lucky number:</body></html>'
        );
    }


}