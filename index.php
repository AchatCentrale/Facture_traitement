<?php
require __DIR__ . '/vendor/autoload.php';

use src\db;

//db
define("hostname", "185.29.149.144");
define("user", "AC_JBAGOSTIN");
define("pwd", "35H7Hjvy");
define("db_name", "CENTRALE_PRODUITS");

//directory

define("dir_pdf", "/var/www/facture_ac/Facture_traitement/public/pdf/");
define("dir_pdf_dev", "/Users/jb/dev/facture_ac/public/pdf/");

$db = new db(hostname, db_name, user, pwd);

$conn = $db->connect();

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
    $client->setAuthConfig('/var/www/Facture_traitement/credentials.json');
    $client->setAccessType('offline');

    // Load previously authorized token from a file, if it exists.
    $tokenPath = '/var/www/Facture_traitement/token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Get Message with given ID.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @param  string $messageId ID of Message to get.
 * @return Google_Service_Gmail_Message Message retrieved.
 */
function getMessage($service, $userId, $messageId) {
    try {
        $message = $service->users_messages->get($userId, $messageId);
        return $message;
    } catch (Exception $e) {
        print 'An error occurred: ' . $e->getMessage();
    }
}


/**
 * Modify the Labels a Message is associated with.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @param  string $messageId ID of Message to modify.
 * @param  array $labelsToAdd Array of Labels to add.
 * @param  array $labelsToRemove Array of Labels to remove.
 * @return Google_Service_Gmail_Message Modified Message.
 */
function modifyMessage($service, $userId, $messageId, $labelsToAdd, $labelsToRemove) {
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setAddLabelIds($labelsToAdd);
    $mods->setRemoveLabelIds($labelsToRemove);
    try {
        $message = $service->users_messages->modify($userId, $messageId, $mods);
        print 'Message with ID: ' . $messageId . ' successfully modified.';
        return $message;
    } catch (Exception $e) {
        print 'An error occurred: ' . $e->getMessage();
    }
}

/**
 * Get list of Messages in user's mailbox.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $query the query for filter the messages
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @return array Array of Messages.
 */
function listMessages($service, $userId, $query) {
    $pageToken = NULL;
    $messages = [];
    $opt_param = [];

    $resultParse = [];
    do {
        try {
            if ($pageToken) {
                $opt_param['pageToken'] = $pageToken;
            }
            if ($query) {
                $opt_param['q'] = $query;
            }
            $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
            if ($messagesResponse->getMessages()) {
                $messages = array_merge($messages, $messagesResponse->getMessages());
                $pageToken = $messagesResponse->getNextPageToken();
            }
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    } while ($pageToken);

    foreach ($messages as $message) {

        $Message = getMessage($service, $userId, $message->getId());

        $Message_headers = $Message->getPayload()->getHeaders();

        $temp_result = [
            "message_id" => $message->getId(),
            "destinataire" => "",
            "expediteur" =>"",
            "sujet" => "",
            "date" => "",
            "corps" => $Message->getSnippet(),
            "pj_filename" => [],
        ];

        foreach ($Message_headers as $header)
        {

	   if ($header["name"] == "To"){
                if($header["name"][0] === "<"){
                    preg_match_all('/<(.*)>/', $header["value"], $matches);
                    $mail_from = $matches[1][0];
                    $temp_result["destinataire"] = $mail_from;
                }else {
                    $temp_result["destinataire"] = $header["value"];
                }
            }



            if ($header["name"] == "From"){
                preg_match_all('/<(.*)>/', $header["value"], $matches);
                $mail_from = $matches[1][0];
                $temp_result["expediteur"] = $mail_from;
            }
            if ($header["name"] == "Subject"){
                $temp_result["sujet"] = $header["value"];

            }
            if ($header["name"] == "Date"){

                $d = new DateTime($header["value"]);
                $isoDate = $d->format("Y-m-d H:i:s");

                $temp_result["date"] = $isoDate;
            }
        }


        $partAttchement = $Message->getPayload()->getParts();


        $compteur = 0;




        foreach ($partAttchement as $pj){



            if($pj["filename"] != null && strlen($pj["filename"]) > 0 && $pj["mimeType"] == "application/pdf" ||  $pj["mimeType"] == "application/octet-stream"){

                //$filename = $pj["filename"];

                $filename = "Facture-".$compteur++.".pdf";

                $pjId = $pj->getBody()["attachmentId"];

                $temp_result['pj_filename'][] = $filename;

                $attachPart = $service->users_messages_attachments->get($userId, $message->getId(), $pjId);
                // Converting to standard RFC 4648 base64-encoding
                // see http://en.wikipedia.org/wiki/Base64#Implementations_and_history
                $data = strtr($attachPart->getData(), array('-' => '+', '_' => '/'));

                if (!file_exists(dir_pdf.$message->getId())) {
                    mkdir(dir_pdf.$message->getId(), 0777, true);
                }
                $fh = fopen(dir_pdf.$message->getId().'/'.$filename, "w+");
                fwrite($fh, base64_decode($data));
                fclose($fh);
            }else {
                $subPartAttach = $pj->getParts();
                $countPj = 0;
                foreach ($subPartAttach as $SubPj) {

                    if($SubPj["filename"] != null && strlen($SubPj["filename"]) > 0 && $SubPj["mimeType"] == "application/pdf"){

                        $countPj++;
                        $filename = "Facture-".$compteur++.".pdf";

                        $pjId = $SubPj->getBody()["attachmentId"];
                        $temp_result['pj_filename'][] = $SubPj["filename"];

                        $attachPart = $service->users_messages_attachments->get($userId, $message->getId(), $pjId);
                        // Converting to standard RFC 4648 base64-encoding
                        // see http://en.wikipedia.org/wiki/Base64#Implementations_and_history
                        $data = strtr($attachPart->getData(), array('-' => '+', '_' => '/'));
                        if (!file_exists(dir_pdf.$message->getId())) {
                            mkdir(dir_pdf.$message->getId(), 0777, true);
                        }
                        $fh = fopen(dir_pdf.$message->getId().'/'.$filename, "w+");
                        fwrite($fh, base64_decode($data));
                        fclose($fh);

                    }


                }

            }

        }




        array_push($resultParse, $temp_result);

        modifyMessage($service, $userId, $message->getId(), ['Label_8684840702178646650'], ["Label_8987365709096313352", "INBOX"] );
    }

    return $resultParse;
}


/**
 * Get all the Labels in the user's mailbox.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @return array Array of Labels.
 */
function listLabels($service, $userId) {
    $labels = array();

    try {
        $labelsResponse = $service->users_labels->listUsersLabels($userId);

        if ($labelsResponse->getLabels()) {
            $labels = array_merge($labels, $labelsResponse->getLabels());
        }

        foreach ($labels as $label) {
            print 'Label with ID: ' . $label->getId() . '<br/>';
        }
    } catch (Excetion $e) {
        print 'An error occurred: ' . $e->getMessage();
    }

    return $labels;
}



// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

$user = 'me';

$messages = listMessages($service, $user, "is:unread label:Facture_non_traite");

$conn = $db->connect();




foreach($messages as $msg){



    $numberOfAttachement = count($msg["pj_filename"]);


    if ($numberOfAttachement == 1){



        $sqlInsert = "IF NOT EXISTS (SELECT * FROM CENTRALE_PRODUITS.dbo.EMAILS_RECUS
                   WHERE ER_ID_MESSAGE = :message_id)
                   BEGIN
                       INSERT INTO CENTRALE_PRODUITS.dbo.EMAILS_RECUS (ER_ID_MESSAGE, ER_EXPEDITEUR, ER_DESTINATAIRE, ER_OBJET, ER_CORPS, ER_DATE, ER_PIECE_JOINTE, INS_DATE, INS_USER, MAJ_USER, MAJ_DATE)
                        VALUES (:message_id, :expediteur, :destinataire, :sujet, :corps, :date, :pj, GETDATE(), 'Facture.test.funecap@gmail.com', 'Facture.test.funecap@gmail.com',GETDATE() )
                   END";

        $query = $conn->prepare($sqlInsert);
        $query->bindParam(":message_id", $msg["message_id"]);
        $query->bindParam(":expediteur", $msg["expediteur"]);
        $query->bindParam(":destinataire", $msg["destinataire"]);
        $query->bindParam(":sujet", $msg["sujet"]);
        $query->bindParam(":corps", $msg["corps"]);
        $query->bindParam(":date", $msg["date"]);
        $query->bindParam(":pj", $msg["pj_filename"][0]);

        $result = $query->execute();

    }else {

        foreach($msg["pj_filename"] as $index => $pjParse){


            $sqlInsert = "IF NOT EXISTS (SELECT * FROM CENTRALE_PRODUITS.dbo.EMAILS_RECUS
                   WHERE ER_ID_MESSAGE = :message_id)
                   BEGIN
                       INSERT INTO CENTRALE_PRODUITS.dbo.EMAILS_RECUS (ER_ID_MESSAGE, ER_EXPEDITEUR, ER_DESTINATAIRE, ER_OBJET, ER_CORPS, ER_DATE, ER_PIECE_JOINTE, INS_DATE, INS_USER, MAJ_USER, MAJ_DATE)
                        VALUES (:message_id, :expediteur, :destinataire, :sujet, :corps, :date, :pj, GETDATE(), 'Facture.test.funecap@gmail.com', 'Facture.test.funecap@gmail.com',GETDATE() )
                   END";

            $query = $conn->prepare($sqlInsert);
            $query->bindParam(":message_id", $msg["message_id"]);
            $query->bindParam(":expediteur", $msg["expediteur"]);
            $query->bindParam(":destinataire", $msg["destinataire"]);
            $query->bindParam(":sujet", $msg["sujet"]);
            $query->bindParam(":corps", $msg["corps"]);
            $query->bindParam(":date", $msg["date"]);
            $query->bindParam(":pj", $pjParse);

            $result = $query->execute();

        }



    }



}

