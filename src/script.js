const parfumData = {
  parfum1: {
    title: "Parfum Lavande",
    characteristics: `
      <p><strong>Gamme :</strong> Classique</p>
      <p><strong>Concentration :</strong> Eau de Parfum</p>
      <p><strong>Public Cible :</strong> Mixte</p>
      <p><strong>Coût Unitaire :</strong> 25 €</p>
      <p><strong>Description :</strong> Un parfum frais et apaisant avec des notes de lavande.</p>
    `,
    savedCost: 0
  },
  parfum2: {
    title: "Parfum Rose",
    characteristics: `
      <p><strong>Gamme :</strong> Premium</p>
      <p><strong>Concentration :</strong> Eau de Toilette</p>
      <p><strong>Public Cible :</strong> Femme</p>
      <p><strong>Coût Unitaire :</strong> 30 €</p>
      <p><strong>Description :</strong> Un parfum doux et romantique aux délicates notes de rose.</p>
    `,
    savedCost: 0
  },
  parfum3: {
    title: "Parfum Musc",
    characteristics: `
      <p><strong>Gamme :</strong> Classique</p>
      <p><strong>Concentration :</strong> Parfum</p>
      <p><strong>Public Cible :</strong> Homme</p>
      <p><strong>Coût Unitaire :</strong> 35 €</p>
      <p><strong>Description :</strong> Un parfum intense et captivant aux notes de musc blanc.</p>
    `,
    savedCost: 0
  }
};

function loadParfumData(parfumKey) {
  const parfum = parfumData[parfumKey];
  document.getElementById('parfum-title').innerText = parfum.title;
  document.getElementById('parfum-characteristics').innerHTML = parfum.characteristics;

  // Afficher le coût précédemment enregistré si existant
  document.getElementById('total-cost').innerText = parfum.savedCost.toFixed(2);
}

function calculateCost() {
  let totalCost = 0;
  const rows = document.querySelectorAll('#bom-table tbody tr');

  rows.forEach(row => {
    const unitCost = parseFloat(row.cells[1].innerText);
    const quantity = parseFloat(row.cells[2].querySelector('input').value);
    const totalIngredientCost = unitCost * quantity;

    row.cells[3].innerText = totalIngredientCost.toFixed(2);
    totalCost += totalIngredientCost;
  });

  document.getElementById('total-cost').innerText = totalCost.toFixed(2);

  // Sauvegarder le coût total pour le parfum actuellement sélectionné
  const parfumKey = document.getElementById('parfum-title').innerText.toLowerCase().replace(" ", "");
  if (parfumData[parfumKey]) {
    parfumData[parfumKey].savedCost = totalCost;
  }
}
