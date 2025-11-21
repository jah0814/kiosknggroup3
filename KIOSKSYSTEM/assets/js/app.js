// Basic utilities and display polling (used by display and admin pages)
async function getJSON(url){
  const res = await fetch(url, { cache: 'no-store' });
  return await res.json();
}

function formatBadge(status){
  return `<span class="badge ${status}">${status}</span>`;
}

window.KioskApp = { getJSON, formatBadge };
