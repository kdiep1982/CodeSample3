<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;

class JsonController extends Controller
{
    public function index(Request $request){
    	
		 $username = $request->json()->get("Username"); 
         $password = $request->json()->get("Password");
		 $external_id = $request->json()->get("ExternalID");
         $is_active = $request->json()->get("IsActive"); 
         $product_name = $request->json()->get("ProductName");
         $product_description = $request->json()->get("ProductDescription"); 
         $product_category = $request->json()->get("ProductCategory"); 
         $eol_date = $request->json()->get("EndOfLifeDate"); 
         $last_support_date = $request->json()->get("LastSupportDate");
         $launch_date = $request->json()->get("LaunchDate");
         $revision = $request->json()->get("Revision");
         $specifications = $request->json()->get("Specifications");
         $support_site_business_type = $request->json()->get("SupportSiteBusinessType");
         $warranty = $request->json()->get("Warranty"); 
         $main_image_path = $request->json()->get("MainImagePath");
		
		 $errmessage = null;
		 $product_id=0;
		 $warranty_id=0;
		 $result=FALSE;
		 $message='';
		 $post_id=0;
		 
		 //get user login info
		 $user = \DB::table('wp_users')->where([
		 ['user_pass','=',$password],
		 ['user_login','=',$username]
		 ])->first();
		
		 /*
		  * Check all required fields, make sure they are valid and not empty
		  */
		  
		 if(empty($username)) $errmessage.='Username is required';
		 if(empty($password)) $errmessage.="Password is required"; 
		 if(empty($user)) $errmessage.="Login incorrect";
		 if(empty($product_name)) $errmessage .="Product Name is required";
		 if(empty($warranty)) $errmessage .="Warranty is required";
		 if(empty($product_description)) $errmessage .="Product Description is required";
		 if(empty($product_category)) $errmessage .="Product Category is required";
		 if(empty($support_site_business_type)) $errmessage .="Support Site Business Type is required";
		 
		 if(empty($errmessage)) {
		 	
			
			 if(empty($this->checkExternalIdExist($external_id)) || empty($external_id)){
			 /*
			  * Insert product into the table
			  * 
			  * */
			  
			 	$external_id=\DB::table('wp_products')->insertGetId([
			 		'title'=>$product_name,
			 		'business_type'=>strtolower($support_site_business_type),
			 		'product_category_id'=>$this->checkProductCategory($product_category,$product_description,$is_active,$support_site_business_type, $user,$warranty),
			 		'warranty_document_id'=>$this->checkWarranty($warranty,$is_active,$support_site_business_type,$user),
			 		'eol_date'=>$eol_date,
			 		'ldos_date'=>$last_support_date,
			 		'model_no'=>$product_name,
			 		'launch_date'=>$launch_date,
			 		'model_name'=>$product_description,
			 		'created_date'=>$this->getCurrentDate(),
			 		'modified_date'=>$this->getCurrentDate(),
			 		'status'=>$is_active,
			 		'image_url'=>$main_image_path,
			 		'user_created'=>$user->ID,
			 		'user_modified'=>$user->ID,
			 		'external_product_id'=>'0'
			 	]); 
				
				if(!empty($external_id)){
					\DB::table('wp_products')->where('id','=',$external_id)->update(['external_product_id'=>$external_id]);
					
					$post_id=\DB::table('wp_posts')->insertGetId([
						'post_author'=>'1',
						'post_date'=>$this->getCurrentDate(),
						'post_date_gmt'=>$this->getCurrentDate(),
						'post_content'=>$product_description,
						'post_title'=>$product_name,
						'post_status'=>'publish',
						'comment_status'=>'closed',
						'ping_status'=>'closed',
						'post_name'=>strtolower($product_name),
						'post_modified'=>$this->getCurrentDate(),
						'post_modified_gmt'=>$this->getCurrentDate(),
						'post_type'=>'page',
						'post_excerpt'=>'',
						'to_ping'=>'',
						'pinged'=>'',
						'post_content_filtered'=>''
						
						
						
					]);
					\DB::table('wp_posts')->where('id','=',$post_id)->update(['guid'=>'http://10.6.8.161/?p='.$post_id]);
					\DB::table('wp_post_product')->insert([
						'post_id'=>$post_id,
						'product_id'=>$external_id
					]);
					\DB::table('wp_postmeta')->insert([
						'post_id'=>$post_id,
						'meta_key'=>'_wp_page_template',
						'meta_value'=>'product-page.php'
						
					]);
				}
				
				$message="Insert Successfully";
			}
	
			else{
				$result=\DB::table('wp_products')->where('external_product_id','=',$external_id)->update([
					'title'=>$product_name,
				 		'business_type'=>strtolower($support_site_business_type),
				 		'product_category_id'=>$this->checkProductCategory($product_category,$product_description,$is_active,$support_site_business_type,$user, $warranty),
				 		'warranty_document_id'=>$this->checkWarranty($warranty,$is_active,$support_site_business_type,$user),
				 		'eol_date'=>$eol_date,
				 		'ldos_date'=>$last_support_date,
				 		'model_no'=>$product_name,
				 		'launch_date'=>$launch_date,
				 		'model_name'=>$product_description,
				 		'created_date'=>$this->getCurrentDate(),
				 		'modified_date'=>$this->getCurrentDate(),
				 		'status'=>$is_active,
				 		'image_url'=>$main_image_path,
				 		'user_created'=>$user->ID,
				 		'user_modified'=>$user->ID
				]);
				
				if($is_active=="0") {
					
					//update page to private to disable the product
					
					\DB::table('wp_posts')->where('post_title','=',$product_name)->update(['post_status'=>'private']);
					
				}
				
					//update page to publish to re enable the product
					
				else if($is_active=="1"){
					\DB::table('wp_posts')->where('post_title','=',$product_name)->update(['post_status'=>'publish']);
				}
				
				$message="Update Successfully";
			}
			
			if($result || !empty($external_id)){
				return response()->json([
					'Message'=>$message,
					'IsInsert'=>'true',
					'IsSuccess'=>'true',
					'ExternalID'=>$external_id
				]);
			}
			else {
				return response()->json([
					'Message'=>'Fail update/insert',
					'IsInsert'=>'false',
					'IsSuccess'=>'false',
					'ExternalID'=>'NaN'
				]);
			}
		}
		else{
			return response()->json([
				'Message'=>$errmessage,
				'IsInsert'=>'false',
				'IsSuccess'=>'false',
				'ExternalID'=>'NaN'
			]);
		}
		
	}

	public function checkExternalIdExist($externalID){
		$exist = \DB::table('wp_products')->where('external_product_id','=',$externalID)->first();
		return $exist;
	}
	
	public function checkProductCategory($product_category,$product_description, $is_active, $support_site_business_type, $user,$warranty){
		$product_id=0;
		$product=\DB::table('wp_product_category')->where('product_category','=',$product_category)->first();
		if(empty($product)){
		 		$product_id=\DB::table('wp_product_category')->insertGetId([
		 			'warranty_external_id'=>$this->checkWarranty($warranty, $is_active, $support_site_business_type, $user),
		 			'product_category'=>$product_category,
		 			'description'=>$product_description,
		 			'status'=>$is_active,
		 			'type'=>strtolower($support_site_business_type),
		 			'created_date'=>$this->getCurrentDate(),
		 			'modified_date'=>$this->getCurrentDate(),
		 			'user_created'=>$user->ID,
					'user_modified'=>$user->ID
		 		]);
		 	}
			else{
				$product_id=$product->id;
			}
			
		
		return $product_id;
	}
	
	public function getCurrentDate(){
		$date=date('Y-m-d H:i:s');
		return $date;
	}
	public function checkWarranty($warranty,$is_active,$support_site_business_type, $user) {
		
		$warranty_id=0;
		$warranty_check=\DB::table('wp_warranty')->where('warranty_type','=',$warranty)->first();
					 
		if(empty($warranty_check)) {
				$warranty_id=\DB::table('wp_warranty')->insertGetId([
					'external_warranty_id'=>'0',
					'warranty_type'=>$warranty,
					'url'=>'',
					'status'=>$is_active,
					'type'=>strtolower($support_site_business_type),
					'created_date'=>$this->getCurrentDate(),
					'modified_date'=>$this->getCurrentDate(),
					'user_created'=>$user->ID,
					'user_modified'=>$user->ID
				]);
		}
		else{
			$warranty_id=$warranty_check->id;
		}
		return $warranty_id;
	}
	
	public function revision(Request $request){
		
		 $username = $request->json()->get("Username"); 
         $password = $request->json()->get("Password");
		 $external_id = $request->json()->get("ExternalID");
         $is_active = $request->json()->get("IsActive"); 
		 $revision=$request->json()->get("Revision");
		 $product_id=$request->json()->get("ProductExternalID");
		 
		 $user = \DB::table('wp_users')->where([
			 ['user_pass','=',$password],
			 ['user_login','=',$username]
		 ])->first();
		
		$errmessage='';
		$message ='';
		$result=false;
		if(empty($username)) $errmessage.='Username is required';
		if(empty($password)) $errmessage.="Password is required"; 
		if(empty($user)) $errmessage.="Login incorrect";
		if(empty($revision)) $errmessage .="Revision is required";
		if(empty($product_id)) $errmessage .="Product ID is required";
		
		if(empty($errmessage)){
			if(!empty($external_id)) {
				
				/*
				 * update the revision table
				 */
				 
				$result = \DB::table('wp_revisions')->where('id','=',$external_id)->update([
					'revision'=>$revision,
					'product_id'=>$product_id,
					'status'=>$is_active
				]);
				
				$message="Update successfully";
			}
			
			else{
				$external_id = \DB::table('wp_revisions')->insertGetId([
					'revision'=>$revision,
					'product_id'=>$product_id,
					'status'=>$is_active
				]);
				$message="Insert Successfully";
			}
			
			if($result || !empty($product_id)) {
				return response()->json([
					'Message'=>$message,
					'IsInsert'=>'true',
					'IsSuccess'=>'true',
					'ExternalID'=>$external_id
				]);
			}
		}
		else{
			return response()->json([
				'Message'=>$errmessage,
				'IsInsert'=>'false',
				'IsSuccess'=>'false',
				'ExternalID'=>'NaN'
			]);
		}
		
	}

	
}
