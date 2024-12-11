const express = require('express');
const router = express.Router();
const parfumController = require('../controllers/parfumController');

router.get('/', parfumController.getParfums);
router.get('/:id', parfumController.getParfumDetails);

module.exports = router;
