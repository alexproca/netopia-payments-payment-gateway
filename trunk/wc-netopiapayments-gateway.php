<?php
class netopiapayments extends WC_Payment_Gateway {
	// Setup our Gateway's id, description and other values
	function __construct() {	
		
		$this->id = "netopiapayments";
		$this->method_title = __( "NETOPIA Payments", 'netopiapayments' );
		$this->method_description = __( "NETOPIA Payments Payment Gateway Plug-in for WooCommerce", 'netopiapayments' );
		$this->title = __( "NETOPIA", 'netopiapayments' );
		$this->icon = NTP_PLUGIN_DIR . 'img/netopiapayments.gif';
		$this->has_fields = true;
		$this->notify_url        	= WC()->api_request_url( 'netopiapayments' );

		// Supports the default credit card form
		$this->supports = array(
	               'products',
	               'refunds'
	               );//array( 'default_credit_card_form' );
		
		$this->init_form_fields();
		
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// Lets check for SSL
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		add_action('init', array(&$this, 'check_netopiapayments_response'));
		//update for woocommerce >2.0
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_netopiapayments_response' ) );

		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}	

		add_action('woocommerce_receipt_netopiapayments', array(&$this, 'receipt_page'));
	} // End __construct()	

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(			
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'netopiapayments' ),
				'label'		=> __( 'Enable this payment gateway', 'netopiapayments' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'environment' => array(
				'title'		=> __( 'NETOPIA Payments Test Mode', 'netopiapayments' ),
				'label'		=> __( 'Enable Test Mode', 'netopiapayments' ),
				'type'		=> 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'netopiapayments' ),
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'netopiapayments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'netopiapayments' ),
				'default'	=> __( 'NETOPIA Payments', 'netopiapayments' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'netopiapayments' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'netopiapayments' ),				
				'css'		=> 'max-width:350px;'
			),
			'account_id' => array(
				'title'		=> __( 'Seller Account ID', 'netopiapayments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is Account ID provided by Netopia when you signed up for an account. Unique key for your seller account for the payment process.', 'netopiapayments' ),
				'description' => __( 'Login to Netopia and go to Admin-> Conturi de comerciant->Modifica (iconita creionas)->tab-ul Setari securitate', 'netopiapayments' ),
			),
			'payment_methods'   => array(
		        'title'       => __( 'Payment methods', 'netopiapayments' ),
		        'type'        => 'multiselect',
		        'description' => __( 'Select which payment methods to accept.', 'netopiapayments' ),
		        'default'     => '',
		        'options'     => array(
		          'credit_card'	      => __( 'Credit Card', 'netopiapayments' ),
		          'sms'			        => __('SMS' , 'netopiapayments' ),
		          'bank_transfer'		      => __( 'Bank Transfer', 'netopiapayments' ),
		          'bitcoin'  => __( 'Bitcoin', 'netopiapayments' )
		          ),
		    ),	
			'sms_setting' => array(
				'title'       => __( 'For SMS Payment', 'netopiapayments' ),
				'type'        => 'title',
				'description' => '',
			),	
			'service_id' => array(
				'title'		=> __( 'Product/service code: ', 'netopiapayments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is Service Code provided by Netopia when you signed up for an account.', 'netopiapayments' ),
				'description' => __( 'Login to Netopia and go to Admin -> Conturi de comerciant -> Produse si servicii -> Semnul plus', 'netopiapayments' ),
			),	
		);		
	}

	function payment_fields() {
		$user = wp_get_current_user();
      	// Description of payment method from settings
      	if ( $this->description ) { ?>
        	<p><?php echo $this->description; ?></p>
  		<?php }
  		if ( $this->payment_methods ) {  
  			$payment_methods = $this->payment_methods;	
  		}else{
  			$payment_methods = array('credit_card');
  		}
  		//echo "<pre>payment_methods: "; print_r($payment_methods); echo "</pre>";
  		$name_methods = array(
		          'credit_card'	      => __( 'Credit Card', 'netopiapayments' ),
		          'sms'			        => __('SMS' , 'netopiapayments' ),
		          'bank_transfer'		      => __( 'Bank Transfer', 'netopiapayments' ),
		          'bitcoin'  => __( 'Bitcoin', 'netopiapayments' )
		          );
  		?>
  		<div id="netopia-methods">
	  		<ul>
	  		<?php  foreach ($payment_methods as $method) { ?>
	  			<?php 
	  			$checked ='';
	  			if($method == 'credit_card') $checked = 'checked="checked"';
	  			?>
	  				<li>
	  					<input type="radio" name="netopia_method_pay" class="netopia-method-pay" id="netopia-method-<?=$method?>" value="<?=$method?>" <?php echo $checked; ?> /><label for="inspire-use-stored-payment-info-yes" style="display: inline;"><?php echo $name_methods[$method] ?></label>
	  				</li> 			
	  		<?php } ?>
	  		</ul>
  		</div>

  		<style type="text/css">
  			#netopia-methods{display: inline-block;}
  			#netopia-methods ul{margin: 0;}
  			#netopia-methods ul li{list-style-type: none;}
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function($){				
				var method_ = $('input[name=netopia_method_pay]:checked').val();
				if(method_!='sms'){
					$('.billing-shipping').show('slow');
				}else{
					$('.billing-shipping').hide('slow');
				}

				//console.log('method_: ',method_);
				$('.netopia-method-pay').click(function(){
					var method = $(this).val();
					//console.log('method: ',method);
					if(method!='sms'){
						$('.billing-shipping').show('slow');
					}else{
						$('.billing-shipping').hide('slow');
					}					
				});
			});
		</script>
  		<?php
  	}

  	// Submit payment
	public function process_payment( $order_id ) {
		global $woocommerce;		
		$order = new WC_Order( $order_id );			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
				/* 2.1.0 */
				$checkout_payment_url = $order->get_checkout_payment_url( true );
			} else {
				/* 2.0.0 */
				$checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
			}

			$method = $this->get_post( 'netopia_method_pay' );
			return array(
				'result' => 'success', 
				'redirect' => add_query_arg(
					'method', 
					$method, 
					add_query_arg(
						'key', 
						$order->order_key, 
						$checkout_payment_url						
					)
				)
        	);
    }

	// Validate fields
	public function validate_fields() {
		$method_pay            = $this->get_post( 'netopia_method_pay' );
		// Check card number
		if ( empty( $method_pay ) ) {
			wc_add_notice( __( 'Alege metoda de plata.', 'netopiapayments' ), $notice_type = 'error' );
			return false;
		}
		return true;
	}

  	/**
	* Receipt Page
	**/
	function receipt_page($order){
		$customer_order = new WC_Order( $order );
		$order_amount = sprintf('%.2f',$customer_order->get_total());
		echo '<p>'.__('Multumim pentru comanda, te redirectionam in pagina de plata NETOPIA payments.', 'netopiapayments').'</p>';
		echo '<p><strong>'.__('Total', 'netopiapayments').": ".$customer_order->get_total().' '.$customer_order->get_currency().'</strong></p>';
		echo $this->generate_netopia_form($order);
	}

	/**
	* Generate payment button link
	**/
	function generate_netopia_form($order_id){
		global $woocommerce;
		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );
		$user = new WP_User( $customer_order->get_user_id());
		
		// Are we testing right now or is it a real transaction
		//$environment = ( $this->environment == 'yes' ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$paymentUrl = ( $this->environment == 'yes' ) 
						   ? 'http://sandboxsecure.mobilpay.ro/'
						   : 'https://secure.mobilpay.ro/';
		if ($this->environment == 'yes') {
			$x509FilePath = plugin_dir_path( __FILE__ ).'netopia/sandbox.'.$this->account_id.'.public.cer';
		}
		else {
			$x509FilePath = plugin_dir_path( __FILE__ ).'netopia/live.'.$this->account_id.'.public.cer';
		}

		require_once 'netopia/Payment/Request/Abstract.php';		
		require_once 'netopia/Payment/Invoice.php';
		require_once 'netopia/Payment/Address.php';

		$method = $this->get_post( 'method' );
		$name_methods = array(
		          'credit_card'	      => __( 'Credit Card', 'netopiapayments' ),
		          'sms'			        => __('SMS' , 'netopiapayments' ),
		          'bank_transfer'		      => __( 'Bank Transfer', 'netopiapayments' ),
		          'bitcoin'  => __( 'Bitcoin', 'netopiapayments' )
		          );
		switch ($method) {
			case 'sms':		
				require_once 'netopia/Payment/Request/Sms.php';
				$objPmReq = new Netopia_Payment_Request_Sms();	
				$objPmReq->service 		= $this->service_id;	
				break;
			case 'bank_transfer':
				require_once 'netopia/Payment/Request/Transfer.php';
				$objPmReq = new Netopia_Payment_Request_Transfer();	
				$paymentUrl .= '/transfer';
				break;
			case 'bitcoin':	
				require_once 'netopia/Payment/Request/Bitcoin.php';
				$objPmReq = new Netopia_Payment_Request_Bitcoin();			
				$paymentUrl = 'https://secure.mobilpay.ro/bitcoin'; //for both sanbox and live
				break;
			default: // credit_card
				require_once 'netopia/Payment/Request/Card.php';
				$objPmReq = new Netopia_Payment_Request_Card();
				break;
		}
		
		srand((double) microtime() * 1000000);
		$objPmReq->signature 			= $this->account_id;
		$objPmReq->orderId 				= md5(uniqid(rand()));
		$objPmReq->confirmUrl 			= $this->notify_url;
		$objPmReq->returnUrl 			= htmlentities(WC_Payment_Gateway::get_return_url( $customer_order ));
		
		if($method != 'sms'){
			$objPmReq->invoice = new Netopia_Payment_Invoice();
			$objPmReq->invoice->currency	= $customer_order->get_currency();;//$customer_order->get_order_currency();//;get_woocommerce_currency();
			$objPmReq->invoice->amount		= sprintf('%.2f',$customer_order->get_total());//sprintf('%.2f',$customer_order->order_total);
			$objPmReq->invoice->details		= 'Plata pentru comanda cu ID: '.$order_id.' with '.$name_methods[$method];

			$billingAddress 				= new Netopia_Payment_Address();
			$billingAddress->type			= 'person';//$_POST['billing_type'];
			$billingAddress->firstName		= $customer_order->get_billing_first_name();
			$billingAddress->lastName		= $customer_order->get_billing_last_name();
			$billingAddress->address		= $customer_order->get_billing_address_1();
			$billingAddress->email			= $customer_order->get_billing_email();
			$billingAddress->mobilePhone	= $customer_order->get_billing_phone();
			$objPmReq->invoice->setBillingAddress($billingAddress);

			$shippingAddress 				= new Netopia_Payment_Address();
			$shippingAddress->type			= 'person';//$_POST['shipping_type'];
			$shippingAddress->firstName		= $customer_order->get_shipping_first_name();
			$shippingAddress->lastName		= $customer_order->get_shipping_last_name();
			$shippingAddress->address		= $customer_order->get_shipping_address_1();
			$shippingAddress->email			= $customer_order->get_billing_email();
			$shippingAddress->mobilePhone	= $customer_order->get_billing_phone();
			$objPmReq->invoice->setShippingAddress($shippingAddress);
		}		
		
		$objPmReq->params = array('order_id'=>$order_id,'customer_id'=>$customer_order->get_user_id(),'customer_ip'=>$_SERVER['REMOTE_ADDR'],'method'=>$method);	try {	
		$objPmReq->encrypt($x509FilePath);
		//echo "<pre>objPmReq: "; print_r($objPmReq); echo "</pre>";
		return '	<form action="'.$paymentUrl.'" method="post" id="frmPaymentRedirect">
				<input type="hidden" name="env_key" value="'.$objPmReq->getEnvKey().'"/>
				<input type="hidden" name="data" value="'.$objPmReq->getEncData().'"/>				
				<input type="submit" class="button-alt" id="submit_netopia_payment_form" value="'.__('Plateste prin NETOPIA payments', 'netopiapayments').'" /> <a class="button cancel" href="'.$customer_order->get_cancel_order_url().'">'.__('Anuleaza comanda &amp; goleste cosul', 'netopiapayments').'</a>
				<script type="text/javascript">
				jQuery(function(){
				jQuery("body").block({
					message: "'.__('Iti multumim pentru comanda. Te redirectionam catre NETOPIA payments pentru plata.', 'netopiapayments').'",
					overlayCSS: {
						background		: "#fff",
						opacity			: 0.6
					},
					css: {
						padding			: 20,
						textAlign		: "center",
						color			: "#555",
						border			: "3px solid #aaa",
						backgroundColor	: "#fff",
						cursor			: "wait",
						lineHeight		: "32px"
					}
				});
				jQuery("#submit_netopia_payment_form").click();});
				</script>
			</form>';
		} catch (\Throwable $th) {
			// throw $th;
			echo '<p><i style="color:red">Asigura-te ca ai incarcat toate cele 4 chei de securitate, 2 pentru mediul live, 2 pentru mediul sandbox! Citeste cu atentie instructiunile din manual!</i></p>
				 <p style="font-size:small">Ai in continuare probleme? Trimite-ne doua screenshot-uri la <a href="mailto:implementare@netopia.ro">implementare@netopia.ro</a>, unul cu setarile metodei de plata din adminul wordpress si unul cu locatia in care ai incarcat cheile (de preferat sa se vada denumirea completa a cheilor si calea completa a locatiei)</p>';
		}
	}	

	/**
	* Check for valid NETOPIA server callback
	**/
	function check_netopiapayments_response(){
		// $this->bpLog('response ========================================');
		// $this->bpLog($_POST);
		global $woocommerce;

		require_once 'netopia/Payment/Request/Abstract.php';

		require_once 'netopia/Payment/Request/Card.php';
		require_once 'netopia/Payment/Request/Sms.php';
		require_once 'netopia/Payment/Request/Transfer.php';
		require_once 'netopia/Payment/Request/Bitcoin.php';

		require_once 'netopia/Payment/Request/Notify.php';
		require_once 'netopia/Payment/Invoice.php';
		require_once 'netopia/Payment/Address.php';

		$errorCode 		= 0;
		$errorType		= Netopia_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
		$errorMessage	= '';
		
		$msg_errors = array('16'=>'card has a risk (i.e. stolen card)', '17'=>'card number is incorrect', '18'=>'closed card', '19'=>'card is expired', '20'=>'insufficient funds', '21'=>'cVV2 code incorrect', '22'=>'issuer is unavailable', '32'=>'amount is incorrect', '33'=>'currency is incorrect', '34'=>'transaction not permitted to cardholder', '35'=>'transaction declined', '36'=>'transaction rejected by antifraud filters', '37'=>'transaction declined (breaking the law)', '38'=>'transaction declined', '48'=>'invalid request', '49'=>'duplicate PREAUTH', '50'=>'duplicate AUTH', '51'=>'you can only CANCEL a preauth order', '52'=>'you can only CONFIRM a preauth order', '53'=>'you can only CREDIT a confirmed order', '54'=>'credit amount is higher than auth amount', '55'=>'capture amount is higher than preauth amount', '56'=>'duplicate request', '99'=>'generic error');
		
		if ($this->environment == 'yes') {
			$privateKeyFilePath 	= plugin_dir_path( __FILE__ ).'netopia/sandbox.'.$this->account_id.'private.key';
		}
		else {
			$privateKeyFilePath 	= plugin_dir_path( __FILE__ ).'netopia/live.'.$this->account_id.'private.key';
		}

		if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0){
			if(isset($_POST['env_key']) && isset($_POST['data'])){
				try
				{
					$objPmReq = Netopia_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath);
					$action = $objPmReq->objPmNotify->action;

					// $this->bpLog('THParams :     ');
					// $this->bpLog($objPmReq->params);
					// $this->bpLog('Notify :     ');
					// $this->bpLog($objPmReq->objPmNotify);

					$params = $objPmReq->params;
					$order = new WC_Order( $params['order_id'] );
					$user = new WP_User( $params['customer_id'] );
					$transaction_id = $objPmReq->objPmNotify->purchaseId;
					if($objPmReq->objPmNotify->errorCode==0){
						switch($action)
			    		{
			    			case 'confirmed':
								#cand action este confirmed avem certitudinea ca banii au plecat din contul posesorului de card si facem update al starii comenzii si livrarea produsului
								//update DB, SET status = "confirmed/captured"
								$errorMessage = $objPmReq->objPmNotify->errorMessage;
								
								$amountorder_RON = $objPmReq->objPmNotify->originalAmount; //$objPmReq->objPmNotify->originalAmount
								$amount_paid = is_null($objPmReq->objPmNotify->originalAmount) ? 0:$objPmReq->objPmNotify->originalAmount;
								//$objPmReq->objPmNotify->originalAmount
								//original_amount -> the original amount processed;
								//processed_amount -> the processed amount at the moment of the response. It can be lower than the original amount, ie for capturing a smaller amount or for a partial credit
								if( $amount_paid < $amountorder_RON ) {
					                //Update the order status
									$order->update_status('on-hold', '');

									//Error Note
									$message = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
									$message_type = 'notice';

									//Add Customer Order Note
				                    $order->add_order_note($message.'<br />Netopia Transaction ID: '.$transaction_id, 1);

				                    //Add Admin Order Note
				                    $order->add_order_note('Look into this order. <br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was &#8358; '.$amount_paid.' RON while the total order amount is &#8358; '.$amountorder_RON.' RON<br />Netopia Transaction ID: '.$transaction_id);

									// Reduce stock levels
									wc_reduce_stock_levels($order->get_id());

									// Empty cart
									wc_empty_cart();
								}
								else {
									if( $order->get_status() == 'processing' ) {
					                    $order->add_order_note('Plata prin NETOPIA payments<br />Transaction ID: '.$transaction_id);

					                    //Add customer order note
					 					$order->add_order_note('Plata receptionata.<br />Comanda este in curs de procesare.<br />Vom face livrarea in curand.<br />NETOPIA Transaction ID: '.$transaction_id, 1);

										// Reduce stock levels
										wc_reduce_stock_levels($order->get_id());

										// Empty cart
										wc_empty_cart();

										//$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';
										//$message_type = 'success';
					                }
					                else {

					                	if( $order->has_downloadable_item() ) {

					                		//Update order status
											$order->update_status( 'completed', 'Payment received, your order is now complete.' );

						                    //Add admin order note
						                    $order->add_order_note('Plata prin NETOPIA payments<br />Transaction ID: '.$transaction_id);

						                    //Add customer order note
						 					$order->add_order_note('Payment Received.<br />Your order is now complete.<br />NETOPIA Transaction ID: '.$transaction_id, 1);

											//$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is now complete.';
											//$message_type = 'success';

					                	}
					                	else {

					                		//Update order status
											$order->update_status( 'completed', 'Payment received, your order is currently being processed.' );

											//Add admin order noote
						                    $order->add_order_note('Plata prin NETOPIA payments<br />Transaction ID: '.$transaction_id);

						                    //Add customer order note
						 					$order->add_order_note('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />NETOPIA Transaction ID: '.$transaction_id, 1);

											$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';
											$message_type = 'success';
					                	}

										// Reduce stock levels
										wc_reduce_stock_levels($order->get_id());

										// Empty cart
										wc_empty_cart();
					                }
					            }
								break;
							case 'paid':
								//Update order status -> to be added, but on-hold should work for now
								$order->update_status( 'on-hold', 'Your payment is currently being processed.' );
								//Add admin order note
						        $order->add_order_note('Payment remotely accepted via NETOPIA, make sure to capture it<br />Transaction ID: '.$transaction_id);
								break;	
							case 'confirmed_pending':
								//Update order status
								$order->update_status( 'on-hold', 'Your payment is currently being processed.' );
								//Add admin order note
						        $order->add_order_note('Payment pending via NETOPIA<br />Transaction ID: '.$transaction_id);
								break;
							case 'paid_pending':
								//Update order status
								$order->update_status( 'on-hold', 'Your payment is currently being processed.' );
								//Add admin order note
						        $order->add_order_note('Payment pending via NETOPIA<br />Transaction ID: '.$transaction_id);
								break;
						    case 'canceled':
								#cand action este canceled inseamna ca tranzactia este anulata. Nu facem livrare/expediere.
								//update DB, SET status = "canceled"
								$errorMessage = $objPmReq->objPmNotify->errorMessage;							

								$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.';
								//Add Customer Order Note
			                   	$order->add_order_note($message.'<br />NETOPIA Transaction ID: '.$transaction_id, 1);

			                    //Add Admin Order Note
			                  	$order->add_order_note($message.'<br />NETOPIA Transaction ID: '.$transaction_id);

				                //Update the order status
								$order->update_status('cancelled', '');
							    break;
							case 'credit':
								#cand action este credit inseamna ca banii sunt returnati posesorului de card. Daca s-a facut deja livrare, aceasta trebuie oprita sau facut un reverse. 
								//update DB, SET status = "refunded"
								if ($objPmReq->invoice->currency != 'RON') {
									$rata_schimb = $objPmReq->objPmNotify->originalAmount/$objPmReq->invoice->amount;
									}
									else $rata_schimb = 1;
								$refund_amount = $objPmReq->objPmNotify->processedAmount/$rata_schimb;

								$args = array( 
									'amount' => $refund_amount,  
									'reason' => 'Netopia call',  
									'order_id' => $params['order_id'],  
									'refund_id' => null,  
									'line_items' => array(),  
									'refund_payment' => false,  
									'restock_items' => false  
									 ); 
									 
								$refund = wc_create_refund($args);	
								 
								$errorMessage = $objPmReq->objPmNotify->errorMessage;
								$message = 	'Plata rambursata.';
								//Add Customer Order Note
			                   	$order->add_order_note($message.'<br />NETOPIA Transaction ID: '.$transaction_id, 1);

			                    //Add Admin Order Note
			                  	$order->add_order_note($message.'<br />NETOPIA Transaction ID: '.$transaction_id);

								//Update the order status if fully refunded
								if ($refund_amount == $objPmReq->objPmNotify->originalAmount) {
								$order->update_status('refunded', '');
								}
							    break;	
			    		}
					}else{
						$order->update_status('failed', '');

						//Error Note
						$message = $objPmReq->objPmNotify->errorMessage;
						if(empty($message) && isset($msg_errors[$objPmReq->objPmNotify->errorCode])) $message = $msg_errors[$objPmReq->objPmNotify->errorCode];
						$message_type = 'error';
						//Add Customer Order Note
	                    $order->add_order_note($message.'<br />NETOPIA Transaction ID: '.$transaction_id, 1);
					}					
				}catch(Exception $e)
				{
					$errorType 		= Netopia_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
					$errorCode		= $e->getCode();
					$errorMessage 	= $e->getMessage();
				}
			}else
			{
				$errorType 		= Netopia_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
				$errorCode		= Netopia_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
				$errorMessage 	= 'NETOPIA posted invalid parameters';
			}
		}else 
		{
			$errorType 		= Netopia_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
			$errorCode		= Netopia_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
			$errorMessage 	= 'invalid request method for payment confirmation';
		}
		// $this->bpLog('errorType: '.$errorType.' -- errorCode: '.$errorCode.' -- errorMessage: '.$errorMessage);
		header('Content-type: application/xml');
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		if($errorCode == 0)
		{
			echo "<crc>{$errorMessage}</crc>";
		}
		else
		{
			echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
			
		}	
		// wc_empty_cart();
		die();
	}


	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

	/**
	 * Get post data if set
	 */
	private function get_post( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			return $_REQUEST[ $name ];
		}
		return null;
	}

	public function bpLog($contents,$file=false){
		if(!$file)	$file = dirname(__FILE__).'/bplog.txt';
		file_put_contents($file, date('m-d H:i:s').": ", FILE_APPEND);
		
		if (is_array($contents))
			$contents = var_export($contents, true);	
		else if (is_object($contents))
			$contents = json_encode($contents);
			
		file_put_contents($file, $contents."\n", FILE_APPEND);			
	}

}
