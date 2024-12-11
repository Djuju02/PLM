exports.getParfums = (req, res) => {
    res.send([
      { id: 1, name: 'Lavande', price: 25 },
      { id: 2, name: 'Rose', price: 30 },
      { id: 3, name: 'Musc', price: 35 },
    ]);
  };
  
  exports.getParfumDetails = (req, res) => {
    const { id } = req.params;
    const parfums = [
      { id: 1, name: 'Lavande', details: 'Notes fraîches de lavande.' },
      { id: 2, name: 'Rose', details: 'Arôme doux et romantique.' },
      { id: 3, name: 'Musc', details: 'Parfum intense et captivant.' },
    ];
    const parfum = parfums.find((p) => p.id === parseInt(id));
    if (parfum) {
      res.send(parfum);
    } else {
      res.status(404).send({ message: 'Parfum non trouvé' });
    }
  };
  