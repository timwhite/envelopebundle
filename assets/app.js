/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import 'datatables/media/css/jquery.dataTables.css';
import '@danielfarrell/bootstrap-combobox/css/bootstrap-combobox.css';
import 'daterangepicker/daterangepicker.css';

const $ = require('jquery');
window.jQuery = $;
window.$ = $;
const $dataTables = require('datatables.net');
const $combobox = require('@danielfarrell/bootstrap-combobox');
const $daterangepicker = require('daterangepicker');
const $chartJs = require('chart.js')

global.chartJs = window.chartJs = $chartJs;

require('jquery-sparkline');

$('.datatable').DataTable({
    "paging": true,
    "searching": true,
    "ordering": true,
    "info": true,
    "autoWidth": true,
    "order": [[ 0, "desc" ]],
});

$('.combobox').combobox({bsVersion: '3', iconRemove: 'fas fa-caret-down'});


// Transaction page, assign unassigned amount
$('.budgetransactionamount').focus(function () {
        var $unassigned=$('#unassignedamount').data('unassignedamount');
        if (!this.value && $unassigned != 0) {
            this.value = $unassigned;
        }
    }
)

// Sparklines
$('.sparkline').sparkline('html', {valueSpots: {'1:': 'green', ':-1': 'red'}, height: '4em', width: '15em'});