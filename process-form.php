<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function logError($message) {
    file_put_contents('error.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Crear directorio de uploads si no existe
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $formData = json_decode($_POST['formData'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'whirpoolexperts@gmail.com';
    $mail->Password = 'shsbsmrloxtxyoxz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('whirpoolExperts@gmail.com', 'WhirlpoolExpert');
    $mail->addAddress($formData['contact']['email']);
    $mail->addAddress('whirpoolExperts@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = "Confirmación de Cita - WhirlpoolExpert";

    // Procesar imágenes
    $imageHtml = '<h3>Imágenes del electrodoméstico:</h3>';
    $imagePaths = [];

    // Función para procesar cada imagen
    function processImage($file, $type, &$mail, &$imageHtml, &$imagePaths, $uploadDir) {
        if (isset($_FILES[$file]) && $_FILES[$file]['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_' . $type . '.' . $extension;
            $filePath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES[$file]['tmp_name'], $filePath)) {
                $cid = md5($newFileName);
                $mail->addEmbeddedImage($filePath, $cid);
                $imageHtml .= "<p><strong>Imagen " . ucfirst($type) . ":</strong><br>";
                $imageHtml .= "<img src='cid:" . $cid . "' style='max-width: 500px; height: auto;'></p>";
                $imagePaths[] = $filePath;
                return true;
            }
        }
        return false;
    }

    // Procesar ambas imágenes
    processImage('frontImage', 'frontal', $mail, $imageHtml, $imagePaths, $uploadDir);
    processImage('detailImage', 'detalle', $mail, $imageHtml, $imagePaths, $uploadDir);

    // Construir el mensaje
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { text-align: center; padding: 20px; background: #f3f4f6; }
            img { max-width: 100%; height: auto; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Tu cita ha sido confirmada!</h1>
            </div>
            <div class='content'>
                <h2>Detalles de tu cita:</h2>
                <p><strong>Servicio:</strong> " . htmlspecialchars($formData['service']) . "</p>
                <p><strong>Electrodoméstico:</strong> " . htmlspecialchars($formData['appliance']['type']) . "</p>
                <p><strong>Marca:</strong> " . htmlspecialchars($formData['appliance']['brand']) . "</p>
                <p><strong>Fecha:</strong> " . htmlspecialchars($formData['datetime']['date']) . "</p>
                <p><strong>Horario:</strong> " . htmlspecialchars($formData['datetime']['timeSlot']) . "</p>
                <p><strong>Nombre:</strong> " . htmlspecialchars($formData['contact']['nombre']) . "</p>
                <p><strong>Teléfono:</strong> " . htmlspecialchars($formData['contact']['telefono']) . "</p>
                <p><strong>Dirección:</strong> " . htmlspecialchars($formData['contact']['direccion']) . "</p>
                <p><strong>Ciudad:</strong> " . htmlspecialchars($formData['contact']['ciudad']) . "</p>
                <p><strong>Referencias:</strong> " . htmlspecialchars($formData['contact']['referencias']) . "</p>
                $imageHtml
            </div>
            <div class='footer'>
                <p>Gracias por confiar en WhirlpoolExpert</p>
                <p>Si necesitas modificar tu cita, por favor contáctanos</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $mail->Body = $message;

    // Enviar el correo
    if(!$mail->send()) {
        throw new Exception('Error al enviar el correo: ' . $mail->ErrorInfo);
    }

    // Limpiar archivos temporales
    foreach ($imagePaths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // Responder al cliente
    echo json_encode([
        'success' => true,
        'message' => 'Cita programada con éxito'
    ]);

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}
?>

