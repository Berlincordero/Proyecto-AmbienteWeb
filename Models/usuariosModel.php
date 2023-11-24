<?php
    include_once("database.php");

    class Usuarios{
        public $username;
        public $password;
        public $activo;

        public function __construct($username,$password){
            $this -> username = $username;
            $this -> password = $password;
        }
    }

    class UsuariosModel{
        public function ObtenerTodos() {
            $database = OpenDataBase();
            $result = $database->query("SELECT * FROM Usuarios u");
            $usuarios = $result->fetch_all(MYSQLI_ASSOC);
            closeDataBase($database);
            return $usuarios;
        }

        public function Eliminar($id) {
            $database = OpenDataBase();
            $stmtRoles = $database->prepare("DELETE FROM Roles WHERE id_usuario = ?");
            $stmtRoles->bind_param("i", $id);
            $stmtRoles->execute();

            $stmtUsuarios = $database->prepare("DELETE FROM Usuarios WHERE id_usuario = ?");
            $stmtUsuarios->bind_param("i", $id);
            $stmtUsuarios->execute();
            closeDataBase($database);
        }

        public function Obtener($id) {
            $database = OpenDataBase();
            $stmt = $database->prepare("SELECT u.*, r.descripcion AS rol_descripcion FROM Usuarios u LEFT JOIN Roles r ON u.id_usuario = r.id_usuario WHERE u.id_usuario = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $result = $stmt->get_result();
            $empleado = $result->fetch_assoc();
            closeDataBase($database);
            return $empleado;
        }

        public function Modificar($id, $usuario, $password, $descripcion) {
            $sqlUsuarios = "UPDATE Usuarios SET ";
            $paramsUsuarios = array();
            $paramTypesUsuarios = "";
            
            if ($usuario !== null) {
                array_push($paramsUsuarios, "usuario=?");
                $paramTypesUsuarios .= "s";
            }
            
            if ($password !== null) {
                array_push($paramsUsuarios, "pass=?");
                $paramTypesUsuarios .= "s";
            }
            
            $sqlUsuarios .= join(", ", $paramsUsuarios);
            $sqlUsuarios .= " WHERE id_usuario = ?";
            $paramTypesUsuarios .= "i";
            
            $paramValuesUsuarios = array_filter([$usuario, $password, $id], function ($value) {
                return $value !== null;
            });
            
            $database = OpenDataBase();
            $stmtUsuarios = $database->prepare($sqlUsuarios);
            if (!$stmtUsuarios) {
                die('Error en la preparación de la consulta Usuarios: ' . $database->error);
            }
            
            array_unshift($paramValuesUsuarios, $paramTypesUsuarios);
            $stmtUsuarios->bind_param(...$paramValuesUsuarios);
            $stmtUsuarios->execute();
            
            if ($stmtUsuarios->error) {
                die('Error en la ejecución de la consulta Usuarios: ' . $stmtUsuarios->error);
            }
            
            $stmtUsuarios->close();
            
            if ($descripcion !== null) {
                $sqlRoles = "UPDATE Roles SET descripcion=? WHERE id_usuario = ?";
                $stmtRoles = $database->prepare($sqlRoles);
            
                if (!$stmtRoles) {
                    die('Error en la preparación de la consulta Roles: ' . $database->error);
                }
            
                $stmtRoles->bind_param("si", $descripcion, $id);
                $stmtRoles->execute();
            
                if ($stmtRoles->error) {
                    die('Error en la ejecución de la consulta Roles: ' . $stmtRoles->error);
                }
            
                $stmtRoles->close();
            }
            
            closeDataBase($database);
        }        
    }

    class Identity extends Usuarios{
        public function __construct($username,$password){
            parent::__construct($username,$password);
        }

        public function register($rol) {
            $conexion = OpenDataBase();
            $sqlUsuario = "INSERT INTO usuarios (usuario, password, activo) VALUES (?, ?, 1)";
            
            try {
                $stmtUsuario = mysqli_prepare($conexion, $sqlUsuario);
                mysqli_stmt_bind_param($stmtUsuario, "ss", $this->username, $this->password);
                $resultUsuario = mysqli_stmt_execute($stmtUsuario);
        
                if ($resultUsuario) {
                    $idUsuario = mysqli_insert_id($conexion);
                    
                    if ($idUsuario) {
                        $sqlRol = "INSERT INTO roles (descripcion, id_usuario) VALUES (?, ?)";
                        $stmtRol = mysqli_prepare($conexion, $sqlRol);
                        mysqli_stmt_bind_param($stmtRol, "si", $rol, $idUsuario);
                        
                        try {
                            $resultRol = mysqli_stmt_execute($stmtRol);
                            closeDataBase($conexion);
                            return $resultRol;
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            closeDataBase($conexion);
                            return false;
                        }
                    } else {
                        echo "Error al obtener el ID del usuario recién registrado.";
                        closeDataBase($conexion);
                        return false;
                    }
                } else {
                    echo "Error al insertar nuevo usuario.";
                    closeDataBase($conexion);
                    return false;
                }
            } catch (Exception $e) {
                closeDataBase($conexion);
                echo $e->getMessage();
                return false;
            }
        }        

        public function validate($rol){
            $sql = "SELECT 1 
            FROM usuarios u
            JOIN roles r ON u.id_usuario = r.id_usuario
            WHERE u.usuario = ? AND u.pass = ? AND r.descripcion = ?";
            try 
            {
                $conexion = OpenDataBase();
                $stmt = mysqli_prepare($conexion, $sql);
                if (!$stmt) {
                    die("Error en la preparación de la consulta: " . mysqli_error($conexion));
                }
                echo "Consulta SQL: " . $sql;
                mysqli_stmt_bind_param($stmt, "sss", $this->username, $this->password, $rol);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0)
                {
                    mysqli_stmt_close($stmt);
                    mysqli_close($conexion);
                    return true;

                }else{
                    mysqli_stmt_close($stmt);
                    mysqli_close($conexion);
                }
            }
            catch(Exception $e) 
            {
                echo "Error: " . $e->getMessage();
            }
        
        return false;
        }
    }
?>