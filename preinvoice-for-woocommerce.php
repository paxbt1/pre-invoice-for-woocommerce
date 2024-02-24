<?php
/*
Plugin Name: پیش‌فاکتور ووکامرس
Description: Add a 'Download Invoice' button in WooCommerce Cart.
Version: 1.5
Author: Saeed Ghourbanian
*/

if (!defined('ABSPATH')) {
     exit; // Exit if accessed directly
}


// Add admin menu
add_action('admin_menu', 'invoice_plugin_admin_menu');

function invoice_plugin_admin_menu() {
    add_options_page(
        'تنظیمات پیش فاکتور', // Page title
        'تنظیمات پیش فاکتور', // Menu title
        'manage_options', // Capability
        'invoice-plugin-settings', // Menu slug
        'invoice_plugin_settings_page' // Callback function
    );
}

// Define settings fields and register them
function invoice_plugin_settings_init() {
    register_setting(
        'invoice_plugin_settings_group', // Option group
        'enable_proceed_to_checkout_hook' // Option name
    );

    add_settings_section(
        'invoice_plugin_settings_section', // ID
        'Plugin Settings', // Title
        'invoice_plugin_settings_section_cb', // Callback function
        'invoice-plugin-settings' // Page
    );

    add_settings_field(
        'enable_proceed_to_checkout_hook', // ID
        'Enable Proceed to Checkout Hook', // Title
        'enable_proceed_to_checkout_hook_cb', // Callback function
        'invoice-plugin-settings', // Page
        'invoice_plugin_settings_section' // Section
    );
}
add_action('admin_init', 'invoice_plugin_settings_init');

// Section callback
function invoice_plugin_settings_section_cb() {
    echo '<p>Configure settings for the Invoice Plugin</p>';
}

// Checkbox field callback
function enable_proceed_to_checkout_hook_cb() {
    $enable_proceed_to_checkout_hook = get_option('enable_proceed_to_checkout_hook');
    echo '<input type="checkbox" id="enable_proceed_to_checkout_hook" name="enable_proceed_to_checkout_hook" value="1" ' . checked(1, $enable_proceed_to_checkout_hook, false) . '/>';
}

// Settings page callback
function invoice_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>Invoice Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('invoice_plugin_settings_group'); ?>
            <?php do_settings_sections('invoice-plugin-settings'); ?>
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

// Add a custom button after the cart table

add_shortcode('pre-invoice','add_custom_invoice_button');
// Function to add custom invoice button

$enable_proceed_to_checkout_hook = get_option('enable_proceed_to_checkout_hook');
if ($enable_proceed_to_checkout_hook) {
	add_action('woocommerce_proceed_to_checkout', 'add_custom_invoice_button');
}


function add_custom_invoice_button()
{

?>
    <!-- Adding a spinner element -->
    <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.generate-pdf-invoice', function(e) {
                e.preventDefault();

                // Disable the button and show spinner
                $(this).prop('disabled', true);
                $('.generate-pdf-spinner').show();

                $.ajax({
                    type: 'POST',
                    url: "<?= admin_url('admin-ajax.php') ?>", // WordPress AJAX URL
                    data: {
                        action: 'generate_pdf_pre_invoice',
                        nonce: "<?= wp_create_nonce('pdfinvoice') ?>",
                        name: $('#name').val(),
                        id: $('#id').val(),
                        postcode: $('#postcode').val(),
                        phone: $('#phone').val(),
                        address: $('#address').val(),
                        gateway_id: $('#payment_gateway_select').val()
                    },
                    success: function(response) {
                        // Convert base64-encoded content to Blob
                        var byteCharacters = atob(response);
                        var byteNumbers = new Array(byteCharacters.length);
                        for (var i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        var byteArray = new Uint8Array(byteNumbers);
                        var pdfBlob = new Blob([byteArray], {
                            type: 'application/pdf'
                        });

                        // Create a temporary anchor element to trigger download
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(pdfBlob);
                        link.download = 'invoice.pdf';
                        link.click();

                        // Re-enable the button and hide the spinner
                        $('.generate-pdf-invoice').prop('disabled', false);
                        $('.generate-pdf-spinner').hide();
                        $('#myModal').css('display', 'none');
                    },
                    error: function() {
                        // Re-enable the button and hide the spinner in case of an error
                        $('.generate-pdf-invoice').prop('disabled', false);
                        $('.generate-pdf-spinner').hide();
                    }
                });
            });
            $(document).on('click', '#openModal', function() {
                $('#myModal').css('display', 'block');
            });
            $('.close').on('click', function() {
                $('#myModal').css('display', 'none');
            });

        });
    </script>
    <style>
        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner:before {
            content: '';
            box-sizing: border-box;
            position: absolute;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            margin-top: -10px;
            border-radius: 50%;
            border-top: 2px solid #fff;
            border-right: 2px solid transparent;
            animation: spinner .6s linear infinite;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            text-align: center;
        }

        @media (max-width:780px) {

            .modal-content {
                width: 100%;
            }

            .personal-info {
                flex-direction: column;
            }

            .personal-info div {
                margin-bottom: 10px;
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .personal-info {
            display: flex;
            justify-content: space-between;
            text-align: right;
            margin-bottom: 25px;
        }
		#openModal {
    position: relative;
    padding-left: 25px; /* Adjust as needed */
}

#openModal::before {
    content: '';
    position: absolute;
    right: -5px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px; /* SVG width */
    height: 18px; /* SVG height */
    background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M16.5 4.5V6.315C16.5 7.5 15.75 8.25 14.565 8.25H12V3.0075C12 2.175 12.6825 1.5 13.515 1.5C14.3325 1.5075 15.0825 1.8375 15.6225 2.3775C16.1625 2.925 16.5 3.675 16.5 4.5Z" stroke="%23446EFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.5 5.25V15.75C1.5 16.3725 2.205 16.725 2.7 16.35L3.9825 15.39C4.2825 15.165 4.7025 15.195 4.9725 15.465L6.2175 16.7175C6.51 17.01 6.99 17.01 7.2825 16.7175L8.5425 15.4575C8.805 15.195 9.225 15.165 9.5175 15.39L10.8 16.35C11.295 16.7175 12 16.365 12 15.75V3C12 2.175 12.675 1.5 13.5 1.5H5.25H4.5C2.25 1.5 1.5 2.8425 1.5 4.5V5.25Z" stroke="%23446EFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.75 9.75757H9" stroke="%23446EFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.75 6.75757H9" stroke="%23446EFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.49609 9.75H4.50358" stroke="%23446EFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.49609 6.75H4.50358" stroke="%23446EFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>');
    background-repeat: no-repeat;
}

    </style>
    <button id="openModal" class="checkout-button" style="background: transparent;color: #446EFF;">دریافت پیش‌فاکتور</button>

    <div id="myModal" class="modal">
        <div class="modal-content">

            <span class="close">&times;</span>
            <h2>لطفا مشخصات درخواست دهنده تسهیلات را وارد کنید</h2>

            <div class="personal-info">
                <div>
                    <label for="name">نام و نام خانوادگی:</label>
                    <input type="text" id="name" name="name">
                </div>
                <div>
                    <label for="id">کد ملی:</label>
                    <input type="text" id="id" name="id">
                </div>
                <div>
                    <label for="phone">شماره تماس:</label>
                    <input type="text" id="phone" name="phone">
                </div>
            </div>
            <div style="display: flex;justify-content: space-between;text-align: right;margin-bottom: 25px;">
                <div style="width: 100%;margin-left: 15px;">
                    <label for="address">نشانی:</label>
                    <input type="text" id="address" name="address">
                </div>
                <div>
                    <label for="postcode">کد پستی: </label>
                    <input type="text" id="postcode" name="postcode">

                </div>
            </div>

            <p>
                <a href="#" class="button alt generate-pdf-invoice checkout-button" style="background: #2BCF9A;">دریافت پیش‌فاکتور<span class="spinner generate-pdf-spinner" style="display: none;"></span></a>
            </p>
        </div>
    </div>


<?php
}



// Create an AJAX endpoint to handle the PDF generation
add_action('wp_ajax_generate_pdf_pre_invoice', 'generate_pdf_pre_invoice_ajax');
add_action('wp_ajax_nopriv_generate_pdf_pre_invoice', 'generate_pdf_pre_invoice_ajax'); // For non-logged in users

function generate_pdf_pre_invoice_ajax()
{
    if (!wp_verify_nonce($_POST['nonce'], 'pdfinvoice')) {
        wp_die('Security check error!');
    }

    // Load WooCommerce
    if (!class_exists('WooCommerce')) {
        include_once WC_ABSPATH . 'includes/wc-core-functions.php';
        include_once WC_ABSPATH . 'includes/class-wc-cart.php';
        include_once WC_ABSPATH . 'includes/class-wc-checkout.php';
    }

    // require_once(get_stylesheet_directory() . 'vendor/autoload.php'); // Path to mPDF autoload file
    require_once __DIR__ . '/vendor/autoload.php';

    $formatter = new IntlDateFormatter(
        "fa_IR@calendar=persian",
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        "yyyy/MM/dd"
    );
    $now = new DateTime();

    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',

        'fontDir' => array_merge($fontDirs, [__DIR__]),
        'fontdata' => $fontData +
            [
                'vazir' => [
                    'R' => 'assets/Vazirmatn-Regular.ttf',
                ],
                'inkfree' => [
                    'R' => 'assets/Vazirmatn-Bold.ttf',
                ],
            ],
    ]);
    $mpdf->SetDisplayMode('fullpage');


    ob_clean();
    $html = '
    <!DOCTYPE html>
<html lang="fa">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        table {
            direction: rtl;
            width: 100%;
        }
        th, td {
            padding: 8px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            
        }
        th{
            padding: 10px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
        }
        .seller {
            display: table;
            padding: 8px;
            background: #E3E3E3;
            margin: 0;
            width: 100%;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            text-align: right;
        }
        .info-table td{
            border:0.1mm solid #3D3D3D;
            border-collapse: collapse !important; 
            border-spacing: 0px;
        }
    </style>
</head>
    <body>
    
    <table style="direction: rtl;" autosize="2.4">
    <tr>
    
        <th style="font-size:14px;"><img style="vertical-align: top" src="' . __DIR__ . '/assets/alpha.png" width="129" /></th>    
        <th style="font-size:14px;" >پیش فاکتور فروش کالا و خدمات</th>
        <th style="direction:rtl;font-size: 12px;font-weight: 400;line-height: 19px;letter-spacing: 0em;text-align: right;" >تاریخ: ' . $formatter->format($now) . '</th>
    </tr>
    </table>

    <table>
    <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td>فروشنده:</td>
        
    </tr>
    </table>
    <table style="background:#F3F3F3;">
    <tr>
        <td>نام شخص حقیقی/حقوقی: مهر آوران بازار نوین</td>
        <td>شماره ثبت: 570695</td>
        <td>شناسه اقتصادی: 14009694200</td>
        <td>شناسه ملی: 14009694200</td>
    </tr>
        <tr>
            <td>تلفن: 021-22957274</td>
            <td>کد پستی: 1667716365</td>
            <td colspan="2">نشانی: تهران میدان هروی بلوار پناهی نیا بین دهم و دوازدهم پلاک ۷۷ واحد ۲ </td>
        </tr>
        </table>
        <table>
    <tr class="seller" style="display: table; padding: 0px;border:0;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td colspan="4">خریدار:</td>
    </tr>
    </table>
    <table style="background:#F3F3F3;margin:0;padding:0;">
        <tr>
            <td colspan="2">نام شخص حقیقی/حقوقی:' . $_POST['name'] . '</td>
        <td>کد ملی: ' . $_POST['id'] . '</td>
        <td colspan="2">کد پستی: ' . $_POST['postcode'] . '</td>
        <td colspan="2">شماره تماس: ' . $_POST['phone'] . '        </td>
        <tr>
            
        <td>نشانی: ' . $_POST['address'] . '</td>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="4">مشخصات کالا یا خدمات موردمعامله:</th>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;"> 
            <th>ردیف</th>
            <th colspan="2">کالا</th>
            <th>تعداد</th>
            <th colspan="2">قیمت فی (تومان)</th>
        </tr>';

    // Get WooCommerce cart content
    $cart = WC()->cart->get_cart();
    $total_sum = 0;

    if (isset($_POST['gateway_id'])) {
        $gateway_id = $_POST['gateway_id'];
        $naghd_over = isset(get_option('woocommerce_' . $gateway_id . '_settings')['naghd_over']) ? get_option('woocommerce_' . $gateway_id . '_settings')['naghd_over'] : '';
        $naghd_over = floatval($naghd_over);
    }
    $i = 1;

    foreach ($cart as $item_key => $item) {
        $product = $item['data'];
        $product_name = $product->get_name();
        $product_price = $product->get_price();
        $new_price = $product_price + ($product_price * ($naghd_over / 100));
        $quantity = $item['quantity'];
        $total_sum += $new_price * $quantity;
        $html .=    '<tr class="info-table">
                    <td>' . $i . '</td>
                    <td colspan="2">' . $product_name . '</td>
                    <td>' . $quantity . '</td>
                    <td colspan="2">' . number_format($new_price) . '</td>
                </tr>';

        $i++;
    }
    $total_sum = number_format($total_sum);

    $html .= '
    </table>
        <table>
     <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="3">مجموع: </th>
            <th colspan="3" >' . $total_sum . ' تومان </th>
        </tr>
    </table>
    <br>
    <p style="direction:rtl; text-align:right; font-size:14px;">اعتبار این پیش‌فاکتور ۲۴ ساعت بوده و فروشگاه هیچ گونه مسئولیتی بابت تغییر قیمت کالا پس از آن ندارد.</p>
</body>

</html>';
    // wp_die($html);
    $mpdf->WriteHTML($html);
    ob_clean();
    // Output the PDF as a download
    $pdf_content = $mpdf->Output('invoice.pdf', \Mpdf\Output\Destination::STRING_RETURN);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice.pdf"');
    echo base64_encode($pdf_content);
    exit;
}


//====================================================
// ستون مشتری سفارش
//====================================================

add_filter('manage_shop_order_posts_columns', function ($columns) {
    $x = new WC_GolrangLeasing;
    if ($x->show_loanstatus) {
        $columns['order_invoice'] = __('فاکتور', 'golrang-leasing-payment-for-woocommerce');
    }
    return $columns;
}, 666);

add_action('manage_shop_order_posts_custom_column', function ($column, $order_id) {
    if ($column == 'order_invoice') {
        // Get user ID from order meta
        echo '<a href="#" class="invoice-btn" id=' . $order_id . '><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-down-to-line" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="svg-inline--fa fa-arrow-down-to-line fa-fw fa-lg"><path fill="currentColor" d="M32 480c-17.7 0-32-14.3-32-32s14.3-32 32-32H352c17.7 0 32 14.3 32 32s-14.3 32-32 32H32zM214.6 342.6c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 242.7V64c0-17.7 14.3-32 32-32s32 14.3 32 32V242.7l73.4-73.4c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3l-128 128z" class=""></path></svg></a><span class="custom-spinner generate-pdf-spinner" style="float: right;height: 37px;background: red;display:none;">';
    }
}, 666, 2);



function enqueue_custom_script_for_orders_list()
{
    // Check if it's the admin and on the WooCommerce orders list page
    if (is_admin()) {
        // Enqueue your script.js file
        wp_enqueue_script('script', plugin_dir_url(__FILE__) . '/assets/script.js', array('jquery'), false, true);
        // Pass the admin-ajax.php URL to the script
        wp_localize_script(
            'script',
            'ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pdfinvoice'),
            )
        );

        wp_enqueue_style('style', plugin_dir_url(__FILE__) . '/assets/style.css');
    }
}

add_action('admin_enqueue_scripts', 'enqueue_custom_script_for_orders_list');


//=============================================================

// Create an AJAX endpoint to handle the PDF generation
add_action('wp_ajax_generate_pdf_invoice', 'generate_pdf_invoice_ajax');
add_action('wp_ajax_nopriv_generate_pdf_invoice', 'generate_pdf_invoice_ajax'); // For non-logged in users

function generate_pdf_invoice_ajax()
{
    if (!wp_verify_nonce($_POST['nonce'], 'pdfinvoice')) {
        wp_die('Security check error!');
    }

    // Load WooCommerce
    if (!class_exists('WooCommerce')) {
        include_once WC_ABSPATH . 'includes/wc-core-functions.php';
        include_once WC_ABSPATH . 'includes/class-wc-cart.php';
        include_once WC_ABSPATH . 'includes/class-wc-checkout.php';
    }

    // require_once(get_stylesheet_directory() . 'vendor/autoload.php'); // Path to mPDF autoload file
    require_once __DIR__ . '/vendor/autoload.php';

    $formatter = new IntlDateFormatter(
        "fa_IR@calendar=persian",
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        "yyyy/MM/dd"
    );
    $now = new DateTime();

    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',

        'fontDir' => array_merge($fontDirs, [__DIR__]),
        'fontdata' => $fontData +
            [
                'vazir' => [
                    'R' => 'assets/Vazirmatn-Regular.ttf',
                ],
                'inkfree' => [
                    'R' => 'assets/Vazirmatn-Bold.ttf',
                ],
            ],
    ]);
    $mpdf->SetDisplayMode('fullpage');

    $order = wc_get_order($_POST['order_id']);


    ob_clean();

    $html = '
    <!DOCTYPE html>
<html lang="fa">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        table {
            direction: rtl;
            width: 100%;
        }
        th, td {
            padding: 8px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            
        }
        th{
            padding: 10px;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
        }
        .seller {
            display: table;
            padding: 8px;
            background: #E3E3E3;
            margin: 0;
            width: 100%;
            font-size: 10px;
            font-weight: 500;
            line-height: 16px;
            text-align: right;
        }
        .info-table td{
            border:0.1mm solid #3D3D3D;
            border-collapse: collapse !important; 
            border-spacing: 0px;
        }
    </style>
</head>
    <body>
    
    <table style="direction: rtl;" autosize="2.4">
    <tr>
    
        <th style="font-size:14px;"><img style="vertical-align: top" src="' . __DIR__ . '/assets/alpha.png" width="129" /></th>    
        <th style="font-size:14px;" >فاکتور فروش کالا و خدمات</th>
        <th style="direction:rtl;font-size: 12px;font-weight: 400;line-height: 19px;letter-spacing: 0em;text-align: right;" >تاریخ: ' . $formatter->format($now) . '</th>
    </tr>
    </table>

    <table>
    <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td>فروشنده:</td>
        
    </tr>
    </table>
    <table style="background:#F3F3F3;">
    <tr>
        <td>نام شخص حقیقی/حقوقی: مهر آوران بازار نوین</td>
        <td>شماره ثبت: 570695</td>
        <td>شناسه اقتصادی: 14009694200</td>
        <td>شناسه ملی: 14009694200</td>
    </tr>
        <tr>
            <td>تلفن: 021-22957274</td>
            <td>کد پستی: 1667716365</td>
            <td colspan="2">نشانی: تهران میدان هروی بلوار پناهی نیا بین دهم و دوازدهم پلاک ۷۷ واحد ۲ </td>
        </tr>
        </table>
        <table>
    <tr class="seller" style="display: table; padding: 0px;border:0;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
        <td colspan="4">خریدار:</td>
    </tr>
    </table>
    <table style="background:#F3F3F3;margin:0;padding:0;">
        <tr>
            <td colspan="2">نام شخص حقیقی/حقوقی:' . $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . '</td>
        <td>کد ملی: ' . $order->get_meta('_billing_national_id') . '</td>
        <td colspan="2">کد پستی: ' . $order->get_shipping_postcode() . '</td>
        <td colspan="2">شماره تماس: ' . $order->get_meta('_billing_phone') . '        </td>
        <tr>
            
        <td>نشانی: ' . $order->get_shipping_address_1() . '</td>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="4">مشخصات کالا یا خدمات موردمعامله:</th>
        </tr>
        </table>
        <table>
        <tr class="seller" style="display: table; padding: 8px;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;"> 
            <th>ردیف</th>
            <th colspan="2">کالا</th>
            <th>تعداد</th>
            <th colspan="2">قیمت فی (تومان)</th>
        </tr>';


    $total_sum = 0;


    // Get the order object


    if ($order) {
        $i = 1;

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_name = $product->get_name();
            $product_price = $product->get_price(); // Multiply price by 2
            $new_price = $product_price + ($product_price * ($naghd_over / 100));
            $quantity = $item->get_quantity();
            $total_sum += $new_price * $quantity;
            $html .=    '<tr class="info-table">
                                <td>' . $i . '</td>
                                <td colspan="2">' . $product_name . '</td>
                                <td>' . $quantity . '</td>
                                <td colspan="2">' . number_format($new_price) . '</td>
                            </tr>';

            $i++;
        }

        $total_sum = number_format($total_sum);
    }


    $html .= '
    </table>
        <table>
     <tr class="seller" style="display: table; padding: 8px;background: #E3E3E3;margin: 0;width: 100%;font-size: 10px;font-weight: 500;line-height: 16px;letter-spacing: 0em;text-align: right;">
            <th colspan="3">مجموع: </th>
            <th colspan="3" >' . $total_sum . ' تومان </th>
        </tr>
    </table>
    
</body>

</html>';
    // wp_die($html);
    $mpdf->WriteHTML($html);
    ob_clean();
    // Output the PDF as a download
    $pdf_content = $mpdf->Output('invoice.pdf', \Mpdf\Output\Destination::STRING_RETURN);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice.pdf"');
    echo base64_encode($pdf_content);
    exit;
}

