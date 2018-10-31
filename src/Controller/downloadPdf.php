<?php

namespace App\Controller;

use App\Service\HelperServices;
use Doctrine\DBAL\Connection;
use PhpZip\ZipFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;




/**
 * @Route("/pdf", name="pdf_base")
 */
class downloadPdf extends AbstractController
{
    /**
     * @Route("/", name="base_list")
     */
    public function pdf_list()
    {
        $finder = new Finder();
        $finder->files()->in("/var/www/facture_ac/Facture_traitement/public/pdf/");

        $path_array = [];

        foreach ($finder as $file) {
            if (!in_array($file->getRelativePath(), $path_array))
            {
                array_push($path_array, $file->getRelativePath());
            }
        }
        return $this->render('Base/index.html.twig', [
            "path" => $path_array
        ]);
    }


    /**
     * @Route("/download", name="download_pdf")
     */
    public function downloadSinglePdf( Request $request, HelperServices $helper, Connection $conn)
    {





        $data = $request->query->get('id');

        $query = $helper->proper_parse_str($_SERVER['QUERY_STRING'])["id"];

        $zipFile = new ZipFile();
        $finder = new Finder();
        $finder->files()->in("/var/www/facture_ac/Facture_traitement/public/pdf/");

        foreach ($query as $q){

            $sql = "SELECT ER_ID_MESSAGE FROM CENTRALE_PRODUITS.dbo.EMAILS_RECUS WHERE ER_ID = :id";



            $connFourn = $conn->prepare($sql);
            $connFourn->bindValue(':id', $q);
            $connFourn->execute();
            $result = $connFourn->fetchAll();

            dump($result);
            $localPath = "/var/www/facture_ac/Facture_traitement/public/pdf/".$result["ER_ID_MESSAGE"];
            $zipFile->addDir($localPath);


        }

        try{
            $zipFile->outputAsAttachment("invoices.zip");
        }
        catch(\PhpZip\Exception\ZipException $e){
        }

        finally{
            $zipFile->close();
        }
//        $finder = new Finder();
//        $finder->files()->in("/var/www/facture_ac/Facture_traitement/public/pdf/".$id_message)->files()->name("*.pdf");
//
//        $filecount = 0;
//        $files = glob("/var/www/facture_ac/Facture_traitement/public/pdf/".$id_message ."/*.pdf");
//        if ($files){
//            $filecount = count($files);
//        }
//
//        if ($filecount == 1){
//            foreach ($finder as $file){
//                $file = new File($file->getPathname());
//                return $this->file($file);
//            }
//        }else {
//            $localPath = "/var/www/facture_ac/Facture_traitement/public/pdf/".$id_message;
//            $zipFile = new ZipFile();
//            try{
//                $zipFile
//                    ->addDir($localPath)
//                    ->outputAsAttachment($id_message.".zip");
//            }
//            catch(\PhpZip\Exception\ZipException $e){
//            }
//            finally{
//                $zipFile->close();
//            }
//        }

        return $this->json("ok");


    }


    /**
     * @Route("/download/all", name="download_pdf_all")
     */
    public function downloadAllPdf()
    {

        $finder = new Finder();
        $finder->files()->in("/var/www/facture_ac/Facture_traitement/public/pdf/");

        $zipFile = new ZipFile();

        foreach ($finder as $file) {
            $localPath = "/var/www/facture_ac/Facture_traitement/public/pdf/".$file->getRelativePath();
            $zipFile->addDir($localPath);
        }
        try{
            $zipFile->outputAsAttachment("invoices_all.zip");
        }
        catch(\PhpZip\Exception\ZipException $e){
        }

        finally{
            $zipFile->close();
        }
        return new Response("ok");




    }

}