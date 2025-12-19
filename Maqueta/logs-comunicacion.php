<?php
require_once("config.php");
$conn = connection();

/* ===========================
   TRAER EL NOMBRE DEL CLIENTE
   =========================== */
   // Usa 'nombre' y crea alias 'usuario'
   $sql = "SELECT 
   CC.fecha,
   CC.hora,
   C.nombre AS usuario,
   CC.canal,
   CC.mensaje,
   CC.estado
FROM canal_comunicacion CC
INNER JOIN candidatos C ON CC.idClientes = C.id";

$res = $conn->query($sql);

$logs = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $logs[] = [
            "fecha" => date("d/m/Y", strtotime($r["fecha"])),
            "hora" => substr($r["hora"], 0, 5),
            "usuario" => $r["usuario"], 
            "canal" => $r["canal"],
            "mensaje" => $r["mensaje"],
            "estado" => $r["estado"]
        ];
    }
}

$logsJSON = json_encode($logs, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logs de Comunicación</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" /> 

  <style>
    .btn-outline svg {
      margin-right: 8px;
      transition: transform 0.3s ease;
    }
    .btn-outline:hover svg {
      transform: translateX(-4px);
    }
    /* Estilos básicos para el estado (asumiendo que los tienes en styles.css) */
    .status {
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.85em;
        white-space: nowrap;
    }
    .status.enviado {
        background-color: #e6f7e6; /* Verde claro */
        color: #2e7d32; /* Verde oscuro */
    }
    .status.pendiente {
        background-color: #fffbe6; /* Amarillo claro */
        color: #ff9800; /* Naranja/Amarillo oscuro */
    }
    .status.fallido {
        background-color: #fce7e8; /* Rojo claro */
        color: #c62828; /* Rojo oscuro */
    }
    .leyenda-item {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
        font-size: 0.9em;
    }
    .leyenda-color {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        margin-right: 5px;
    }
    /* Clases de leyenda para usar los colores de status */
    .leyenda-color.verde { background: #2e7d32; }
    .leyenda-color.amarillo { background: #ff9800; }
    /* El estilo para fallido ya está en el HTML, pero lo añado por si acaso */
    .leyenda-color[style*="#f6cdd1"] { background: #c62828; }
  </style>
</head>

<body>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="section-title mb-0">Logs de Comunicación</h1>
      <a href="administrador.php" class="btn btn-outline d-flex align-items-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
              class="bi bi-arrow-left" viewBox="0 0 16 16">
          <path fill-rule="evenodd"
                d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" />
        </svg>
        Regresar al panel
      </a>
    </div>

    <div class="card mb-4 p-3">
      <h3 class="mb-3">Filtros</h3>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Usuario</label>
          <select id="filtroUsuario" class="form-select">
            <option>Todos</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Tipo de comunicación</label>
          <select id="filtroTipo" class="form-select">
            <option>Todos</option>
            <option>Email</option> 
            <option>Calendar</option>
            <option>WhatsApp</option>
            <option>SMS</option>
            <option>Llamada</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Estado</label>
          <select id="filtroEstado" class="form-select">
            <option>Todos</option>
            <option>Enviado</option>
            <option>Pendiente</option>
            <option>Fallido</option>
          </select>
        </div>

        <div class="col-md-3 d-grid">
          <button id="btnAplicarFiltros" class="btn mt-3 btn-primary">Aplicar filtros</button>
        </div>
      </div>
    </div>

    <div class="card p-3">
      <h3 class="mb-3">Historial de mensajes</h3>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Usuario</th>
              <th>Canal</th>
              <th>Mensaje</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            </tbody>
        </table>
      </div>
    </div>

    <div class="leyenda mt-3">
      <div class="leyenda-item"><div class="leyenda-color verde"></div> <span>Enviado</span></div>
      <div class="leyenda-item"><div class="leyenda-color amarillo"></div> <span>Pendiente</span></div>
      <div class="leyenda-item"><div class="leyenda-color" style="background:#f6cdd1;"></div> <span>Fallido</span></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const logsBD = <?php echo $logsJSON; ?>;

    // Rellenar opciones del filtro de usuarios automáticamente
    const filtroUsuario = document.getElementById("filtroUsuario");
    // Usamos Set para obtener nombres únicos, ordenados alfabéticamente
    const nombresUnicos = [...new Set(logsBD.map(log => log.usuario))].sort(); 
    nombresUnicos.forEach(nombre => {
        const op = document.createElement("option");
        op.textContent = nombre;
        op.value = nombre; // Aseguramos que el valor es el nombre
        filtroUsuario.appendChild(op);
    });

    const filtroTipo = document.getElementById("filtroTipo");
    const filtroEstado = document.getElementById("filtroEstado");
    const btnAplicarFiltros = document.getElementById("btnAplicarFiltros");
    const tablaBody = document.querySelector("table tbody");

    function renderTabla(logs) {
        tablaBody.innerHTML = "";
        if (logs.length === 0) {
            tablaBody.innerHTML = '<tr><td colspan="6" class="text-center p-3">No hay logs que coincidan con los filtros.</td></tr>';
            return;
        }

        logs.forEach((log) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${log.fecha}</td>
                <td>${log.hora}</td>
                <td>${log.usuario}</td>
                <td>${log.canal}</td>
                <td>${log.mensaje}</td>
                <td>${estadoHTML(log.estado)}</td>
            `;
            tablaBody.appendChild(tr);
        });
    }

    function estadoHTML(estado) {
        const map = {
            // Se asume que los estados en la DB son 'Enviado', 'Pendiente', 'Fallido'
            Enviado: "enviado",
            Pendiente: "pendiente",
            Fallido: "fallido",
        };
        const clase = map[estado] || "";
        return `<span class="status ${clase}">${estado}</span>`;
    }

    function aplicarFiltros() {
        // Obtenemos el valor exacto seleccionado
        const usuarioSel = filtroUsuario.value;
        const tipoSel = filtroTipo.value;
        const estadoSel = filtroEstado.value;

        const filtrados = logsBD.filter((log) => {
            // El valor de 'Todos' debe ser manejado explícitamente.
            const matchUsuario = usuarioSel === "Todos" || log.usuario === usuarioSel;
            const matchTipo = tipoSel === "Todos" || log.canal === tipoSel;
            const matchEstado = estadoSel === "Todos" || log.estado === estadoSel;
            
            return matchUsuario && matchTipo && matchEstado;
        });

        renderTabla(filtrados);
    }

    document.addEventListener("DOMContentLoaded", () => {
        renderTabla(logsBD);
    });

    // Añadir listeners para aplicar filtros al cambiar la selección
    [filtroUsuario, filtroTipo, filtroEstado].forEach(select => {
        select.addEventListener("change", aplicarFiltros);
    });

    btnAplicarFiltros.addEventListener("click", e => {
        e.preventDefault();
        aplicarFiltros();
    });
  </script>
</body>
</html>