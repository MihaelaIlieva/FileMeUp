<?php
    
   require_once '../../database/UserRequestHandler.php';
   require_once '../../helpers/headers.php';
   require_once '../../database/Db.php';
   require_once('../../helpers/makeSizeReadable.php');

   



   ob_start();
    $targetDir = "../../uploads/";
    date_default_timezone_set("Europe/Sofia");
    
    // Create the directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $countInfo = [];
    $response = array();
    $fileDataCollection = array();
    $fileData = [];
    $countSuccessful = 0;
    $originalCount = 0;
    

    if (isset($_FILES['files'])) {

        $countFiles = count($_FILES['files']['name']);
        $originalCount = $countFiles;

        $maxFileCountAllowed=20;
        if($countFiles > $maxFileCountAllowed) {
            $countInfo['error'] = 'Превишен лимит на брой избрани файлове! Лимитът е 20 файла!';
            $countFiles = $maxFileCountAllowed;
        }
        
        
        $files = $_FILES['files'];
        $uploadFileToDatabase = new UserRequestHandler();
        session_start();
        
        
        for($i = 0; $i < $countFiles ; $i++) {

            $fileName = $files['name'][$i];
            $fileTmpName = $files['tmp_name'][$i]; 
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];
            $fileType = $files['type'][$i];
            $fileOwner = $_SESSION['user_id'];
           
            // creates an array with delimeter "."
            $fileExt = explode('.',$fileName);  
            $fileActualExt = strtolower(end($fileExt)); 
            $maxMBForFileAllowed=30;
            $coeficientForMakingFileSizeBigger=1024;
            $maxFileSize = $maxMBForFileAllowed * $coeficientForMakingFileSizeBigger * $coeficientForMakingFileSizeBigger;

            if($fileError === 0) {

                if($fileSize <= $maxFileSize) {
                    // If a you want to upload file which name already exists - > create unique name -> gets time in microseconds as a number
                    //$fileNewName = uniqid('', true).".".$fileActualExt; 
                    $relFileUploadDestination = '../../uploads/'.$fileName; 
                    $fileAbsoluteDestination = 'uploads/'.$fileName;

                    if(move_uploaded_file($fileTmpName, $relFileUploadDestination)) { 


        $id = $fileName.$fileOwner;
        $id= password_hash($id, PASSWORD_DEFAULT);
                        $fileData = [
                            'id' => $id,
                            'name' => $fileName,
                            'size' => $fileSize,
                            'type' => $fileType,
                            'owner' => $fileOwner,
                            'path' => $fileAbsoluteDestination,
                        ];


                        $result = $uploadFileToDatabase->uploadFile($fileData);
                        
                        if($result["success"]){

                            $response[] = array(
                                'filename' => $fileName,
                                'success' => true,
                                'message' => 'Файлът е качен успешно!'
                            );
                           
                            $data = [
                                'id' => $id,
                                'name' => $fileName,
                                'username' => $_SESSION['username'], 
                                'size' => format_size($fileSize),
                                'date' => date('m/d/Y h:i:s', time())
                            ];

                            $fileDataCollection[] = $data;
                            $countSuccessful++;
                        } else {
                            $response[] = array(
                                'filename' => $fileName,
                                'success' => false,
                                'message' => 'Грешка при качване на файла! Опитайте отново!'
                            );
                        }

                    } else {
                        $response[] = array(
                            'filename' => $fileName,
                            'success' => false,
                            'message' => 'Грешка при качване на файла! Опитайте отново!'
                        );
                    }

                    //header("Location: index.php?uploadsuccess");

                } else {
                    //echo "Файлът е прекалено голям!";
                    $response[] = array(
                        'filename' => $fileName,
                        'success' => false,
                        'message' => 'Грешка при качването на файла! Размерът на файла трябва да е под 30 MB!'
                    );
                }

            } else {
                $errorMessage = getUploadErrorMessage($fileError); 
                $response[] = array(
                    'filename' => $fileName,
                    'success' => false,
                    'message' => 'Грешка при качването на файла! Опитайте отново!',
                    'developerMessage' => $errorMessage
                );
            }
        }

    } else{
        $countInfo['noFiles'] = 'Няма избрани файлове!';
    }
    ob_end_clean();
    $countInfo['success'] = $countSuccessful . ' от ' . $originalCount . ' файла са качени успешно!';
    echo json_encode(['countInfo' => $countInfo, 'response' => $response, 'fileDataCollection' => $fileDataCollection]);



?>