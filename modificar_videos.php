<?php
include('includes/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica si se envió el nuevo nombre del video
    if (isset($_POST['newVideoName'])) {
        $newVideoName = htmlentities($_POST['newVideoName']);
        $oldVideoLocation = htmlentities($_POST['oldVideoLocation']);

        // Actualiza el nombre en la base de datos
        $query = $db->prepare("UPDATE videos SET nombre = :newVideoName WHERE ubicacion = :oldVideoLocation");
        $query->bindParam(":newVideoName", $newVideoName);
        $query->bindParam(":oldVideoLocation", $oldVideoLocation);
        $query->execute();

        // Si se envió un nuevo video, también actualiza la ubicación en la carpeta
        if (isset($_FILES['newVideo']) && $_FILES['newVideo']['error'] === 0) {
            $newVideoTemp = $_FILES['newVideo']['tmp_name'];
            $destination = 'videos/' . basename($_FILES['newVideo']['name']);

            // Elimina el video antiguo si es necesario (opcional)
            unlink(__DIR__ . '/videos/' . $oldVideoLocation);

            // Mueve el nuevo video a la carpeta de destino
            move_uploaded_file($newVideoTemp, $destination);

            // Actualiza la ubicación en la base de datos
            $query = $db->prepare("UPDATE videos SET ubicacion = :newVideoLocation WHERE ubicacion = :oldVideoLocation");
            $query->bindParam(":newVideoLocation", basename($destination));
            $query->bindParam(":oldVideoLocation", $oldVideoLocation);
            $query->execute();
        }

        // Redirige después de la operación POST para evitar el reenvío del formulario al recargar la página
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // No se proporcionó un nuevo nombre del video
        echo 'No se proporcionó un nuevo nombre del video.';
    }
}
?>


<?php
include('includes/main_header.php');
?>

<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <script>window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.5.1.min.js"><\/script>')</script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <title>Subir videos con PHP y MySQL</title>
    <style>
        .video-container {
            position: relative;
        }

        .video-container video,
        .video-container button {
            width: 100%;
            max-width: 100%;
        }

        .edit-button {
            margin-top: 5px;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">

<?php
include('includes/main_header.php');
?>

<!-- Begin page content -->
<hr>
<main role="main" class="flex-shrink-0">

    <div class="container">
        <div class="row">
            <h3 class="mt-5">Ver videos subidos con PHP y MySQL </h3>
        </div>
        <hr>
        <div class="row">
            <?php
            $query = $db->prepare("SELECT * FROM videos ORDER BY id DESC");
            $query->execute();
            $data = $query->fetchAll();
            foreach ($data as $row):
                $ubicacion = $row['ubicacion'];
                echo "<div class='col-md-3 video-container'>";
                echo "<video src='videos/" . $ubicacion . "' controls width='100%' height='200px' ></video>";
                // Botón de editar que abrirá el modal
                echo "<button class='btn btn-primary btn-sm edit-button' onclick='openEditModal(\"" . $ubicacion . "\")'>Editar</button>";
                echo "</div>";
            endforeach;
            ?>
        </div>
    </div>
</main>

<!-- Modal para editar videos -->
<div class="modal" id="videoModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Editar Video</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Formulario de edición -->
                <form id="editForm" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="newVideoName">Nuevo nombre del video:</label>
                        <input type="text" class="form-control" id="newVideoName" name="newVideoName">
                    </div>
                    <div class="form-group">
                        <label for="newVideo">Seleccionar nuevo video:</label>
                        <input type="file" class="form-control" id="newVideo" name="newVideo" accept="video/*">
                    </div>
                    <!-- Agregar campo oculto para almacenar la ubicación del video actual -->
                    <input type="hidden" id="oldVideoLocation" name="oldVideoLocation" value="">
                    <button type="button" class="btn btn-primary" onclick="saveChanges()">Guardar Cambios</button>
                </form>

            </div>

        </div>
    </div>
</div>

<!-- JavaScript -->
<!-- Primera carga -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>

<script>
    function openEditModal(videoSrc, videoName) {
        // Limpia el formulario antes de abrirlo
        $('#editForm')[0].reset();

        // Setea el valor del video actual y su nombre en el formulario
        $('#oldVideoLocation').val(videoSrc);
        $('#newVideoName').val(videoName);
        $('#videoModal').modal('show');
    }

    function saveChanges() {
        console.log('Entró en saveChanges');
        var newVideo = $('#newVideo')[0].files[0];

        // Si no se selecciona un nuevo video, no hagas nada
        if (!newVideo) {
            $('#videoModal').modal('hide');
            return;
        }

        // Crea un objeto FormData para enviar el archivo y el nombre al servidor
        var formData = new FormData();
        formData.append('newVideo', newVideo);
        formData.append('oldVideoLocation', $('#oldVideoLocation').val());
        formData.append('newVideoName', $('#newVideoName').val());

        // Realiza una solicitud AJAX al servidor para guardar el nuevo video
        $.ajax({
            type: 'POST',
            url: 'modificar_videos.php', // Reemplaza con la URL correcta si es diferente
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Aquí puedes manejar la respuesta del servidor
                console.log(response);

                // Cierra el modal después de guardar los cambios
                $('#videoModal').modal('hide');

                // Recarga la página o actualiza la lista de videos según sea necesario
                location.reload(); // Puedes considerar una recarga más eficiente
            },
            error: function (error) {
                // Maneja los errores en la solicitud AJAX
                console.error('Error al guardar cambios:', error);
            }
        });
    }
</script>
</body>
</html>

<script>
    function openEditModal(videoSrc) {
        // Limpia el formulario antes de abrirlo
        $('#editForm')[0].reset();

        // Setea el valor del video actual en el formulario
        $('#oldVideoLocation').val(videoSrc);
        $('#videoModal').modal('show');
    }
</script>
</body>
</html>



