/**
 * 2007-2022 Carmine Di Gruttola
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    cdigruttola <c.digruttola@hotmail.it>
 *  @copyright 2007-2022 Carmine Di Gruttola
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 *
 */

$(document).ready(function () {
    toggleCustomerType();

    $('input[name=customertype]').change(function (e) {
        e.preventDefault();
        toggleCustomerType();
    });
});

function toggleCustomerType() {
    var chk = $('input[name=customertype]:checked').val();

    if (typeof chk !== 'undefined') {
        var obj_company = $('input[name=company]');
        var obj_vat_number = $('input[name=vat_number]');

        if (chk === '0' && (obj_company.val().length > 0 || obj_vat_number.val().length > 0)) {
            chk = '1';
            $('input[name=customertype]').filter('[value=1]').prop('checked', true);
        }

        var obj_sdi = $('input[name=sdi]');
        var obj_pec = $('input[name=pec]');
        var obj_pa = $('input[name=pa]');
        var obj_dni = $('input[name=dni]');

        if (chk === '1') {
            obj_company.closest('.form-group').show(100);
            obj_company.prop('required', true);
            if (!obj_company.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_company.closest('.form-group').find('label.form-control-label').addClass('required');
            }

            obj_dni.closest('.form-group').hide(100);

            obj_company.closest('.form-group').find('div .form-control-comment').html('');
            obj_vat_number.closest('.form-group').show(100);
            obj_vat_number.prop('required', true);
            if (!obj_vat_number.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_vat_number.closest('.form-group').find('label.form-control-label').addClass('required');
            }
            obj_vat_number.closest('.form-group').find('div .form-control-comment').html('');
            obj_sdi.closest('.form-group').show(100);
            if (sdi_required) {
                obj_sdi.prop('required', true);
                if (!obj_sdi.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                    obj_sdi.closest('.form-group').find('label.form-control-label').addClass('required');
                }
                obj_sdi.closest('.form-group').find('div .form-control-comment').html('');
            }
            obj_pec.closest('.form-group').show(100);
            if (pec_required) {
                obj_pec.prop('required', true);
                if (!obj_pec.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                    obj_pec.closest('.form-group').find('label.form-control-label').addClass('required');
                }
                obj_pec.closest('.form-group').find('div .form-control-comment').html('');
            }
            obj_pa.length ? obj_pa.closest('.form-group').closest('.form-group').show(100) : null;
        } else {
            obj_company.closest('.form-group').hide(100);
            obj_company.prop('required', false);
            if (obj_company.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_company.closest('.form-group').find('label.form-control-label').removeClass('required');
            }

            obj_dni.closest('.form-group').show(100);

            obj_vat_number.closest('.form-group').hide(100);
            obj_vat_number.prop('required', false);
            if (obj_vat_number.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_vat_number.closest('.form-group').find('label.form-control-label').removeClass('required');
            }
            obj_sdi.closest('.form-group').hide(100);
            obj_sdi.prop('required', false);
            if (obj_sdi.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_sdi.closest('.form-group').find('label.form-control-label').removeClass('required');
            }
            obj_pec.closest('.form-group').hide(100);
            obj_pec.prop('required', false);
            if (obj_pec.closest('.form-group').find('label.form-control-label').hasClass('required')) {
                obj_pec.closest('.form-group').find('label.form-control-label').removeClass('required');
            }
            obj_pa.length ? obj_pa.closest('.form-group').closest('.form-group').hide(100) : null;
        }
    }
}
