async function runUpdate() {
  const btn = document.getElementById('updateBtn');
  btn.disabled = true;
  btn.textContent = ' Actualizando...';
  try {
    const res = await fetch('backend/run_update.php');
    const json = await res.json();
    alert(json.ok ? 'Actualización completada' : ' Error: ' + json.output);
  } catch(e) {
    alert('Error al conectar con el servidor');
  }
  btn.disabled = false;
  btn.textContent = ' Actualizar datos';
}