function signIn() {
  let oauth2Endpoint = "https://accounts.google.com/o/oauth2/v2/auth";
  let form = document.createElement("form");
  form.setAttribute("method", "GET");
  form.setAttribute("action", oauth2Endpoint);

  // Determine if we're in development or production
  const isLocalhost = window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost";
  const redirectUri = isLocalhost 
    ? "http://127.0.0.1:5500/profile.html"
    : "https://deaa-2405-201-2013-3d57-e171-2476-eff1-439a.ngrok-free.app/auto_mail/profile.html";

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
