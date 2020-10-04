<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
  
  <!-- HTML Form -->
  <form action="" class="contact-form clear" method="post" onSubmit="if (typeof formValidation !== 'undefined') { return formValidation(this) }">
    <div class="row">
      <label for='form-name' class='field-name'>Full Name <span class="color-red">*</span></label>
      <div class="input-placeholder">
        <input autocomplete="off" class="text input-md input-primary ignore-reset-js" maxlength="200" name="form-name" placeholder="Upesh Vishwakarma" type="text" />
      </div>
    </div>

    <div class="row">
      <label for='form-company' class='field-name'>Campany Name <span class="color-red">*</span></label>
      <div class="input-placeholder">
        <input autocomplete="off" class="text input-md input-primary" name="form-company" placeholder="Company" type="text" value="" />
      </div>
    </div>

    <div class="row">
      <label for='form-email' class='field-name'>Email <span class="color-red">*</span></label>
      <div class="input-placeholder">
        <input autocomplete="off" class="text input-md input-primary input-margin ignore-reset-js" name="form-email" placeholder="name@domain.com" type="text" />
      </div>
    </div>

    <div class="row">
      <label></label>
      <div class="btn-contact input-placeholder">

        <input class="contact-submit btn btn-primary btn-lg col-md-3" name="SubmitButtonModal" type="submit" value="Sign Up" />

      </div>
    </div>
  </form>

  <script>
    // Form Validation
    function formValidation() {

      var ContactForm = {},
      mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,15})+$/;
      
      ContactForm.nameField = document.querySelector("[name='form-name']");
      ContactForm.emailField = document.querySelector("[name='form-email']");
      ContactForm.companyField = document.querySelector("[name='form-company']");

      ContactForm.nameError = document.getElementById("name-error");
      ContactForm.emailError = document.getElementById("email-error");
      ContactForm.phoneError = document.getElementById("phone-error");

      function form_validation(fieldName, fieldError, errorMessage, isEmail, emailErrorMessage) {

        //Clear error field
        fieldName.classList.remove("error-border");
        if (fieldError) {
          fieldError.remove();
        }

        if (!isEmail) {

          if (fieldName.value == "") {
            fieldName.classList.remove("error-border");
            fieldName.insertAdjacentHTML('afterend', '<span class="error-message" id="name-error">' + errorMessage + '</span>');
            fieldName.classList.add("error-border");
          }
        } else {

          if (fieldName.value == "") {
            fieldName.insertAdjacentHTML('afterend', '<span class="error-message" id="email-error">' + errorMessage + '</span>');
            fieldName.classList.add("error-border");
          } else if (!fieldName.value.match(mailformat)) {
            fieldName.insertAdjacentHTML('afterend', '<span class="error-message" id="email-error">' + emailErrorMessage + '</span>');
            fieldName.classList.add("error-border");
          } 

        }

      }

      form_validation(ContactForm.nameField, ContactForm.nameError, 'please share your full name');
      form_validation(ContactForm.emailField, ContactForm.emailError, 'please share your email address', true, 'please share your vaild email address');
      form_validation(ContactForm.companyField, ContactForm.phoneError, 'please share name of your company ');

      if (ContactForm.nameField.value == "" || ContactForm.emailField.value == "" || !ContactForm.emailField.value.match(mailformat) || ContactForm.companyField.value == "") {
        return false;
      } else {
        return true;
      }
    }
    
  </script>

  <!-- Sending Form data to Mailchimp on Successful submission -->
  <?php
  if (isset($_POST['SubmitButtonModal'])) {
    $email = sanitize_text_field($_POST['form-email']);
    $formName = sanitize_text_field($_POST['form-name']);
    $formPhone = sanitize_text_field($_POST['form-company']);
  
    $status = 'subscribed'; // "subscribed" or "unsubscribed" or "cleaned" or "pending".
    $tag = 'TagName'; // "TagName" is a tag of your form when we different for different form.

    $list_id = 'XXXXXXXXXX'; // List id is also know as Audience ID.
    $api_key = 'XXXXXXXXXX'; // API Key should always hide.
    $merge_fields = array('FNAME' => $formName, 'ORG' => $formPhone);

    mailchimp_subscriber_status($email, $status, $tag, $list_id, $api_key, $merge_fields, "");
  }
  ?>


  <?php 
    function mailchimp_subscriber_status($email, $status, $tag, $list_id, $api_key, $merge_fields = array())
    {

      $result = '';
      $dc     = substr($api_key, strpos($api_key, '-') + 1);

      $data = array(
        'apikey'        => $api_key,
        'email_address' => $email,
        'status'        => $status,
        'tags'          => array($tag),
        'merge_fields'  => $merge_fields,
      );

      /**
       * Add a new list member
       * API Ref : https://mailchimp.com/developer/reference/lists/list-members
       * */
      $url        = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/';
      $result     = mailchimp_curl_connect($url, 'POST', $api_key, $data);
      $json_array = json_decode($result, true);

      // Check If memmber exists then update member tags
      if ($json_array['title'] == 'Member Exists' && $json_array['status'] == 400) {
        /**
         * Update Merge fields
         * API Ref : https://mailchimp.com/developer/reference/lists/list-members/#patch_/lists/-list_id-/members/-subscriber_hash-
         * */
        $data = array(
          'merge_fields' => $merge_fields,
        );

        $url    = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email));
        $result = mailchimp_curl_connect($url, 'PATCH', $api_key, $data);

        /**
         * Update Tags
         * API Ref : https://mailchimp.com/developer/reference/lists/list-members/list-member-tags
         * */
        $data = array(
          'tags'       => array(
            array(
              'name'   => $tag,
              'status' => 'active',
            ),
          ),
          'is_syncing' => false,
        );

        $url = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)) . '/tags';

        $result = mailchimp_curl_connect($url, 'POST', $api_key, $data);
      }

      // Error if mailchimp is not successfull
      if ($json_array['status'] != 400) {
        if ($json_array['status'] != 'subscribed') {
          echo "<script> 
            var mailchimp_err = 'MailChimp Integration Failed on page ' + window.location.href;
            try{
                throw new Error(mailchimp_err);
            } catch (err) {
              console.error(err);
            }
            </script>";
        }
      }

      return $result;
    }

    // Common function for both GET and POST curl connect
    function mailchimp_curl_connect($url, $request_type, $api_key, $data = array())
    {
      if ($request_type == 'GET') {
        $url .= '?' . http_build_query($data);
      }

      $mch_api = curl_init();

      // Basic Header Authentication
      $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('user:' . $api_key),
      );

      /*
      * Setting up useragent of HTTP Header for MailChimp Server
      * Returing the API Response
      */
      curl_setopt($mch_api, CURLOPT_URL, $url);
      curl_setopt($mch_api, CURLOPT_HTTPHEADER, $headers);

      if ($request_type != 'GET') {
        curl_setopt($mch_api, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
      }

      curl_setopt($mch_api, CURLOPT_RETURNTRANSFER, true);

      // Based on requirement MailChimp API: POST/GET/PATCH/PUT/DELETE
      curl_setopt($mch_api, CURLOPT_CUSTOMREQUEST, $request_type);
      curl_setopt($mch_api, CURLOPT_TIMEOUT, 10);

      // certificate verification for TLS/SSL connection
      curl_setopt($mch_api, CURLOPT_SSL_VERIFYPEER, false);

      if ($request_type != 'GET') {
        curl_setopt($mch_api, CURLOPT_POST, true);
        // send data in json
        curl_setopt($mch_api, CURLOPT_POSTFIELDS, json_encode($data));
      }

      return curl_exec($mch_api);
    }  
  ?>

</body>
</html>