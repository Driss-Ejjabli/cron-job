<?php

$rootDirectory = $_SERVER['DOCUMENT_ROOT'] ; 
require($rootDirectory . "/wp-load.php");
    global $wpdb; 


 function curl($post, $url) {
        $r = wp_remote_post($url, array(
            "sslverify"   => false,
            "method"      => "POST",
            "httpversion" => "1.1",
            "body"        => $post
        ));
        return wp_remote_retrieve_body($r);
    }


// Get data info not added in I&T Express
    $table_name = $wpdb->prefix."orders_no_added_jt_express";
    $rows = $wpdb->get_results( "SELECT * FROM ".$table_name);
    
  //  var_dump($rows);
    foreach ($rows as $row) 
    { 
        $data_order_json=$row->data_order_json;
        $id_shipment_info=$row->id_shipment_info;
        $nbr_test_add=$row->nbr_test_add;
        
        if($nbr_test_add<=5)
        {
            
            $base_Path = "https://woocommerce.jtjms-sa.com/woocommerce/";
            $sign = "AKe62df84bJ3d8e4b1hea2R45j11klsb";
            $signature = hash("sha256", ($data_order_json . $sign));
            
            $post = array(
                'data_param' => $data_order_json,
                'data_sign'	=> $signature,
            );
            try
            {
                // Send data info not added in I&T Express
                $curl = curl($post, $base_Path."api/order/add");
               // echo "try";
                $result = json_decode($curl);
                
                $table_name = $wpdb->prefix."orders_no_added_jt_express";
                
                if (empty($result->success) || $result->success != "1"){

                    $nbr_test_add++;
                    $wpdb->update( $table_name, array( 'nbr_test_add' => $nbr_test_add),array('id'=>$row->id));
                //    echo "UPDATE";
                        
                }else
                {
                    $wpdb->delete( $table_name, array('id' => $row->id )); 
                 //   echo "DELETE".$row->id;
                }
                
            }catch(Exception $ex)
            {
              echo "Error: " . $ex->getMessage();
            }
           
        }else
        {
             // Send alert mail Content info not added in I&T Express
                        $admin_email = get_option('admin_email');
                        $to = $admin_email;
                        $subject = 'J&T Express synchronization error';
                        $body = 'id shipment info :'.$id_shipment_info.'<br /> Info Order :<br /><pre>'.$data_order_json.'</pre>';
                        $headers = array('Content-Type: text/html; charset=UTF-8');
                        
                        wp_mail($to, $subject, $body, $headers);
                        //echo "Send alert mail";
        }
        
    }

           
        

?>