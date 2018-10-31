<?php

namespace App\Controller;

use PhpZip\ZipFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
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
        $finder->files()->in("/Users/jb/dev/facture_ac/public/pdf/");

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
     * @Route("/download/id={id_message}", name="download_pdf")
     */
    public function downloadSinglePdf($id_message)
    {
        $finder = new Finder();
        $finder->files()->in("/Users/jb/dev/facture_ac/public/pdf/".$id_message)->files()->name("*.pdf");

        $filecount = 0;
        $files = glob("/Users/jb/dev/facture_ac/public/pdf/".$id_message ."/*.pdf");
        if ($files){
            $filecount = count($files);
        }

        if ($filecount == 1){
            foreach ($finder as $file){
                $file = new File($file->getPathname());
                return $this->file($file);
            }
        }else {
            $localPath = "/Users/jb/dev/facture_ac/public/pdf/".$id_message;
            $zipFile = new ZipFile();
            try{
                $zipFile
                    ->addDir($localPath)
                    ->outputAsAttachment($id_message.".zip");
            }
            catch(\PhpZip\Exception\ZipException $e){
            }
            finally{
                $zipFile->close();
            }
        }



    }


    /**
     * @Route("/download/all", name="download_pdf_all")
     */
    public function downloadAllPdf()
    {

        $finder = new Finder();
        $finder->files()->in("/Users/jb/dev/facture_ac/public/pdf/");

        $zipFile = new ZipFile();

        foreach ($finder as $file) {
            $localPath = "/Users/jb/dev/facture_ac/public/pdf/".$file->getRelativePath();
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