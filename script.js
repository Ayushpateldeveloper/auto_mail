function signIn() {
  let oauth2Endpoint = "https://accounts.google.com/o/oauth2/v2/auth";
  let form = document.createElement("form");
  form.setAttribute("method", "GET");
  form.setAttribute("action", oauth2Endpoint);

  // Determine if we're in development or production
  const isLocalhost = window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost";
  const redirectUri = isLocalhost 
    ? "http://127.0.0.1:5500/profile.php"
    : "https://30f3-27-54-172-62.ngrok-free.app/auto_mail/profile.php";

  let params = {
    client_id: "109168551440-t1741kigc8090155t5smmmmemb5o8crd.apps.googleusercontent.com",
    redirect_uri: redirectUri,
    response_type: "token",
    scope: "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify",
    include_granted_state: "true",
    state: "pass-through-value"
  };

  for (var p in params) {
    let input = document.createElement("input");
    input.setAttribute("type", "hidden");
    input.setAttribute("name", p);
    input.setAttribute("value", params[p]);
    form.appendChild(input);
  }
  document.body.appendChild(form);
  form.submit();
}

// Assuming 'token' is the variable holding the received access token
function handleToken(token) {
  $.ajax({
    url: 'store_token.php',
    type: 'POST',
    data: { access_token: token },
    success: function(response) {
      console.log(response); // Handle success response
    },
    error: function(xhr, status, error) {
      console.error('Error storing token:', error); // Handle error
    }
  });
}

// Assuming the token is received in the URL hash
window.onload = function() {
  const urlParams = new URLSearchParams(window.location.hash.substring(1));
  const token = urlParams.get('access_token');
  if (token) {
    handleToken(token);
  }
}
