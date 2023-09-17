<?php include("../template/cabecera.php"); ?>

<?php
$txtid = (isset($_POST["txtid"])) ? $_POST["txtid"] : "";
$txtnombre = (isset($_POST["txtnombre"])) ? $_POST["txtnombre"] : "";
$txtimagen = (isset($_FILES["txtimagen"]["name"])) ? $_FILES["txtimagen"]["name"] : "";
$accion = (isset($_POST["accion"])) ? $_POST["accion"] : "";
include("../config/bd.php");

switch ($accion) {
    case "agregar":
        $sentenciaSQL = $conexion->prepare("INSERT INTO libros (nombre, imagen) VALUES (:nombre, :imagen);");
        $sentenciaSQL->bindParam(":nombre", $txtnombre);

        // Agrega una marca de tiempo y fecha al nombre del archivo
        $fecha = new DateTime();
        $nombrearchivo = ($txtimagen != "") ? $fecha->format("Y-m-d_H-i-s") . "_" . $_FILES["txtimagen"]["name"] : "imagen.jpg";

        $tmpimagen = $_FILES["txtimagen"]["tmp_name"];
        if ($tmpimagen != "") {
            move_uploaded_file($tmpimagen, "../../img/" . $nombrearchivo);
        }
        $sentenciaSQL->bindParam(":imagen", $nombrearchivo);

        if ($sentenciaSQL->execute()) {
            echo "Libro agregado correctamente.";
        } else {
            echo "Error al agregar el libro.";
        }
        break;

    case "modificar":
        // Verifica si se seleccionó un archivo para modificar la imagen
        if (!empty($_FILES["txtimagen"]["name"])) {
            // Agrega una marca de tiempo y fecha al nombre del archivo
            $fecha = new DateTime();
            $nombrearchivo = $fecha->format("Y-m-d_H-i-s") . "_" . $_FILES["txtimagen"]["name"];
            // Ruta donde se almacenarán las imágenes (ajusta la ruta según tu configuración)
            $ruta = "../../img/";
            // Mueve el archivo cargado al directorio de imágenes
            move_uploaded_file($_FILES["txtimagen"]["tmp_name"], $ruta . $nombrearchivo);

            // Actualiza el nombre e imagen en la base de datos solo si se cargó una nueva imagen
            $sentenciaSQL = $conexion->prepare("UPDATE libros SET nombre=:nombre, imagen=:imagen WHERE id=:id");
            $sentenciaSQL->bindParam(':nombre', $txtnombre);
            $sentenciaSQL->bindParam(':imagen', $nombrearchivo);
            $sentenciaSQL->bindParam(':id', $txtid);

            if ($sentenciaSQL->execute()) {
                header("location:productos.php");
            } else {
                echo "Error al modificar el libro.";
            }
        } else {
            // Si no se cargó una nueva imagen, actualiza solo el nombre en la base de datos
            $sentenciaSQL = $conexion->prepare("UPDATE libros SET nombre=:nombre WHERE id=:id");
            $sentenciaSQL->bindParam(':nombre', $txtnombre);
            $sentenciaSQL->bindParam(':id', $txtid);

            if ($sentenciaSQL->execute()) {
                header("location:productos.php");
            } else {
                echo "Error al modificar el libro.";
            }
        }
        break;

    case "cancelar":
        header("location:productos.php");
        break;

    case "seleccionar":
        $sentenciaSQL = $conexion->prepare("SELECT * FROM libros WHERE id=:id");
        $sentenciaSQL->bindParam(':id', $txtid);
        $sentenciaSQL->execute();
        $libro = $sentenciaSQL->fetch(PDO::FETCH_LAZY);

        if ($libro) {
            $txtnombre = $libro["nombre"];
            $txtimagen = $libro["imagen"];
        } else {
            echo "Libro no encontrado.";
        }
        break;

    case "borrar":
        $sentenciaSQL = $conexion->prepare("SELECT imagen FROM libros WHERE id=:id");
        $sentenciaSQL->bindParam(':id', $txtid);
        $sentenciaSQL->execute();
        $libro = $sentenciaSQL->fetch(PDO::FETCH_LAZY);
        if (isset($libro["imagen"]) && ($libro["imagen"] != "imagen.jpg")) {
            // Ruta donde se encuentran las imágenes (ajusta la ruta según tu configuración)
            $ruta = "../../img/";

            if (file_exists($ruta . $libro["imagen"])) {
                unlink($ruta . $libro["imagen"]);
            }
        }

        $sentenciaSQL = $conexion->prepare("DELETE FROM libros WHERE id=:id");
        $sentenciaSQL->bindParam(':id', $txtid);

        if ($sentenciaSQL->execute()) {
            header("location:productos.php");
        } else {
            echo "Error al eliminar el libro.";
        }
        break;
}

$sentenciaSQL = $conexion->prepare("SELECT * FROM libros");
$sentenciaSQL->execute();
$listalibros = $sentenciaSQL->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    Datos de libro
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="txtid">ID:</label>
                            <input type="text" required readonly class="form-control" value="<?php echo $txtid; ?>" name="txtid" id="txtid" placeholder="ID">
                        </div>
                        <div class="form-group">
                            <label for="txtnombre">Nombre:</label>
                            <input type="text" required class="form-control" value="<?php echo $txtnombre; ?>" name="txtnombre" id="txtnombre" placeholder="Nombre del libro">
                        </div>
                        <div class="form-group">
                            <label for="txtimagen">Imagen:</label>
                            <br/>
                            <?php 
                            if($txtimagen!=""){ ?>
                                <img class="img-thumbnail rounded" src="../../img/<?php echo $txtimagen; ?>" width="50" alt="" srcset="">
                            
                            <?php }?>
                            <input type="file" class="form-control" name="txtimagen" id="txtimagen" placeholder="Nombre del libro">
                        </div>
                        <div class="btn-group" role="group" aria-label="">
                            <button type="submit" name="accion" <?php echo ($accion=="seleccionar")?"disabled":""; ?> value="agregar" class="btn btn-success">Agregar</button>
                            <button type="submit" name="accion" <?php echo ($accion!="seleccionar")?"disabled":""; ?> value="modificar" class="btn btn-warning">Modificar</button>
                            <button type="submit" name="accion" <?php echo ($accion!="seleccionar")?"disabled":""; ?> value="cancelar" class="btn btn-info">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($listalibros as $libro) { ?>
                        <tr>
                            <td><?php echo $libro["id"];?></td>
                            <td><?php echo $libro["nombre"];?></td>
                            <td>
                                <img class="img-thumbnail rounded" src="../../img/<?php echo $libro["imagen"];?>" width="50" alt="" srcset="">
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="txtid" value="<?php echo $libro["id"];?>"/>
                                    <input type="submit" name="accion" value="seleccionar" class="btn btn-primary"/>
                                    <input type="submit" name="accion" value="borrar" class="btn btn-danger"/>
                                </form>
                            </td>
                        </tr>
                    <?php }?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../template/pie.php"); ?>
