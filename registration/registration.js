window.onload = (function() {

    var register = document.getElementById('register');
    register.addEventListener('click', function(event){
        event.preventDefault();
        const registrationForm = document.getElementById('registration-form');
        const inputs = registrationForm.querySelectorAll('input');
      
       
        //associative array for userData
        const userData = {};
        inputs.forEach(input => {
            userData[input.name] = input.value;
       });
       sendRequest('../registration/registration.php', userData);

    })
  

})