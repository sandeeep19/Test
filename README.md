Amplify Sdk at staging
====

 Amplify is a  customer relationship management  for web app owners
 
 This library provides connectivity with the Amplify API 
  
  Basic usage:
  
  1. Configure Amplify with your access credentials
  <code>
  <?php
  
  $amplify = new Amplify('dummy_app_key','dummy_app_secret','dummy_project_id');
  ?>
  </code>
  
  2. Make requests to the API
  <code>
  <?php
  $amplify = new Amplify('dummy_app_key','dummy_app_secret','dummy_project_id');
  amplify->identify(`sandeep@socialaxishq.com`,‘Sandeep’);
 
  ?>
  </code>
  

 
 *******     Identify system user if it unknowm leave parameter blank *******
      amplify->identify(`sandeep@socialaxishq.com`,‘Sandeep’);
      Replace with name and email of current user
     

 
*******      add new event with properties    *******
      amplify->event(`sandeep@socialaxishq.com`,array('addtocart'=>array('product'=>’Samsung Note2’,'category'=>’Mobile’,'price'=>’456.78’)));
     

  
*******      add user  properties    *******
      amplify->update(`sandeep@socialaxishq.com`,array('country'=>’India’,'city'=>’Noida’));
     


 
 *******     add new user  properties   *******   
      amplify->add(`sandeep@socialaxishq.com`,array('total_comments'=>’5’,'total_shares'=>’4’));
     