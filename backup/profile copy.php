<?php
require_once 'includes/dbcon.php';
include 'includes/header.php';
// Fetch the latest access token from the token table
$query = 'SELECT TOP 1 access_token FROM tokens ORDER BY id DESC';
$result = sqlsrv_query($conn, $query);
if ($result === false) {
  die('Error executing query: ' . print_r(sqlsrv_errors(), true));
}
$access_token = '';
if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
  $access_token = $row['access_token'];
}
sqlsrv_free_stmt($result);
?>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Emails - Dashboard</title>
  <!-- TailwindCSS via CDN (v3) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .table-container { max-height: 620px; overflow-y: auto; }
    tr:hover { background-color: #f3f4f6; }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Filter Section -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex flex-wrap items-center gap-4">
      <span class="text-gray-700 font-semibold">Filter by Keyword:</span>
      <div id="keywordContainer" class="flex flex-wrap gap-3">
        <!-- Keyword checkboxes will be loaded dynamically -->
      </div>
    </div>
  </section>
  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-8 pb-10">
    <!-- Sidebar -->
    <aside class="w-64">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-5">Departments</h2>
        <div id="departmentList" class="space-y-2">
          <!-- Departments will be loaded dynamically -->
        </div>
      </div>
    </aside>
    <!-- Email Display Area -->
    <section class="flex-1 ">
      <!-- Tabs -->
      <div class="flex space-x-4 mb-6">
        <button id="tab-unread" class="px-5 py-2 rounded-md shadow font-semibold bg-blue-600 text-white focus:outline-none">Unread Emails</button>
        <button id="tab-read" class="px-5 py-2 rounded-md shadow font-semibold bg-gray-200 text-gray-800 focus:outline-none">Read Emails</button>
        <button id="tab-sent" class="px-5 py-2 rounded-md shadow font-semibold bg-gray-200 text-gray-800 focus:outline-none">Sent Emails</button>
        <button id="tab-forwarded" class="px-5 py-2 rounded-md shadow font-semibold bg-gray-200 text-gray-800 focus:outline-none">Forwarded Emails</button>
      </div>
      <!-- Unread Emails -->
      <div id="unreadContainer" class="bg-white rounded-lg shadow p-6 h-[74vh] w-[72vw]">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Unread Emails</h2>
        <div class="table-container">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">From</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
              </tr>
            </thead>
            <tbody id="unreadEmailTable" class="divide-y divide-gray-100">
              <tr id="unreadEmailTable-sentinel"><td colspan="5" class="py-4"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Read Emails -->
      <div id="readContainer" class="bg-white rounded-lg shadow p-6 mt-8 hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Read Emails</h2>
        <div class="table-container">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">From</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
              </tr>
            </thead>
            <tbody id="readEmailTable" class="divide-y divide-gray-100">
              <tr id="readEmailTable-sentinel"><td colspan="5" class="py-4"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Sent Emails -->
      <div id="sentContainer" class="bg-white rounded-lg shadow p-6 h-[74vh] w-[72vw] hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Sent Emails</h2>
        <div class="table-container">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
              </tr>
            </thead>
            <tbody id="sentEmailTable" class="divide-y divide-gray-100">
              <tr id="sentEmailTable-sentinel"><td colspan="4" class="py-4"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Forwarded Emails -->
      <div id="forwardedContainer" class="bg-white rounded-lg shadow p-6 h-[74vh] w-[72vw] hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Forwarded Emails</h2>
        <div class="table-container">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
              </tr>
            </thead>
            <tbody id="forwardedEmailTable" class="divide-y divide-gray-100">
              <tr id="forwardedEmailTable-sentinel"><td colspan="4" class="py-4"></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
  <script>
    // Global variables for emails and pagination tokens
    let allEmailsGlobal = [];
    let unreadEmailsGlobal = [];
    let readEmailsGlobal = [];
    let sentEmailsGlobal = [];
    let forwardedEmailsGlobal = [];
    let currentDepartment = null;
    let nextPageTokenInbox = null;  // For inbox (unread/read)
    let nextPageTokenSent = null;   // For sent emails
    let nextPageTokenForwarded = null; // For forwarded emails
    let isLoading = false;
    let hasMoreInboxEmails = true;
    let hasMoreSentEmails = true;
    let hasMoreForwardedEmails = true;

    // Global variables for current filter queries
    let currentInboxQuery = "";
    let currentSentQuery = "in:sent";
    let currentForwardedQuery = "in:sent subject:(Fwd:)";

    // Helper: Format date
    function formatEmailDate(dateString) {
      const d = new Date(dateString);
      if (isNaN(d)) return dateString;
      const day = d.getDate().toString().padStart(2, '0');
      const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      const month = monthNames[d.getMonth()];
      const year = d.getFullYear();
      const nowYear = new Date().getFullYear();
      const hour = d.getHours().toString().padStart(2, '0');
      const minute = d.getMinutes().toString().padStart(2, '0');
      return year === nowYear ? `${day} ${month} ${hour}:${minute}` : `${day} ${month} ${year} ${hour}:${minute}`;
    }

    // Helper: Truncate snippet
    function truncateSnippet(snippet, maxWords = 12) {
      const words = snippet.split(" ");
      return words.length > maxWords ? words.slice(0, maxWords).join(" ") + "..." : snippet;
    }

    // Retrieve access token (passed from PHP)
    let params = {};
    params['access_token'] = '<?php echo $access_token; ?>';
    if (window.location.hash) {
      const queryString = window.location.hash.substring(1);
      let regex = /([^&=]+)=([^&]*)/g, m;
      while (m = regex.exec(queryString)) {
        params[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
      }
    }
    if (Object.keys(params).length > 0) {
      localStorage.setItem('authInfo', JSON.stringify(params));
      $.ajax({
        url: 'store_token.php',
        type: 'POST',
        data: { access_token: JSON.stringify(params) },
        success: function(response) { console.log(response); },
        error: function(xhr, status, error) { console.error('Error storing authInfo:', error); }
      });
    }

    const isLocalhost = window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost";
    const basePath = isLocalhost ? "/" : "/auto_mail/";
    window.history.pushState({}, document.title, window.location.pathname);

    let authInfo = JSON.parse(localStorage.getItem('authInfo'));
    if (!authInfo || !authInfo['access_token']) {
      alert("Access token missing. Please sign in.");
      window.location.href = basePath + "index.html";
    }
    const accessToken = authInfo['access_token'];

    // Fetch email details from Gmail API
    function fetchEmailDetails(messageId) {
      return fetch(`https://gmail.googleapis.com/gmail/v1/users/me/messages/${messageId}?format=full`, {
        headers: { "Authorization": `Bearer ${accessToken}` }
      })
      .then(response => {
        if (!response.ok) throw new Error("Error fetching email details: " + response.status);
        return response.json();
      })
      .then(emailData => {
        let headers = emailData.payload.headers;
        let emailDate = "", emailFrom = "", emailTo = "", emailSubject = "";
        headers.forEach(header => {
          if (header.name === "Date") emailDate = header.value;
          if (header.name === "From") emailFrom = header.value;
          if (header.name === "To") emailTo = header.value;
          if (header.name === "Subject") emailSubject = header.value;
        });
        const internalDate = Number(emailData.internalDate);
        let snippet = emailData.snippet || "";
        snippet = truncateSnippet(snippet);
        const isUnread = emailData.labelIds && emailData.labelIds.includes("UNREAD");
        return { messageId, emailDate, emailFrom, emailTo, emailSubject, snippet, internalDate, isUnread };
      });
    }

    // Fetch inbox emails (unread/read) using currentInboxQuery
    async function fetchEmailsByQuery(query = currentInboxQuery, pageToken = null) {
      if (isLoading) return;
      isLoading = true;
      try {
        let url = "https://gmail.googleapis.com/gmail/v1/users/me/messages?maxResults=20";
        if (query) url += `&q=${encodeURIComponent(query)}`;
        if (pageToken) url += `&pageToken=${pageToken}`;
        const response = await fetch(url, { headers: { "Authorization": `Bearer ${accessToken}` } });
        if (!response.ok) throw new Error("Error fetching emails: " + response.status);
        const data = await response.json();
        if (!data.messages) throw new Error("No messages in response.");
        nextPageTokenInbox = data.nextPageToken;
        hasMoreInboxEmails = !!nextPageTokenInbox;
        const emailPromises = data.messages.map(message => fetchEmailDetails(message.id));
        const emailDetails = await Promise.all(emailPromises);
        emailDetails.forEach(email => {
          allEmailsGlobal.push(email);
          if (email.isUnread) {
            unreadEmailsGlobal.push(email);
          } else {
            readEmailsGlobal.push(email);
          }
        });
        applyFilters();
      } catch (error) {
        console.error('Error fetching emails by query:', error);
        alert("Error fetching emails: " + error.message);
      } finally {
        isLoading = false;
      }
    }

    // Fetch sent emails using currentSentQuery
    async function fetchSentEmailsByQuery(query = currentSentQuery, pageToken = null) {
      if (isLoading) return;
      isLoading = true;
      try {
        let url = "https://gmail.googleapis.com/gmail/v1/users/me/messages?maxResults=20";
        if (query) url += `&q=${encodeURIComponent(query)}`;
        if (pageToken) url += `&pageToken=${pageToken}`;
        const response = await fetch(url, { headers: { "Authorization": `Bearer ${accessToken}` } });
        if (!response.ok) throw new Error("Error fetching sent emails: " + response.status);
        const data = await response.json();
        if (!data.messages) throw new Error("No messages in response.");
        nextPageTokenSent = data.nextPageToken;
        hasMoreSentEmails = !!nextPageTokenSent;
        const emailPromises = data.messages.map(message => fetchEmailDetails(message.id));
        const emailDetails = await Promise.all(emailPromises);
        emailDetails.forEach(email => { sentEmailsGlobal.push(email); });
        applyFilters();
      } catch (error) {
        console.error('Error fetching sent emails by query:', error);
        alert("Error fetching sent emails: " + error.message);
      } finally {
        isLoading = false;
      }
    }

    // Fetch forwarded emails using currentForwardedQuery
    async function fetchForwardedEmailsByQuery(query = currentForwardedQuery, pageToken = null) {
      if (isLoading) return;
      isLoading = true;
      try {
        let url = "https://gmail.googleapis.com/gmail/v1/users/me/messages?maxResults=20";
        if (query) url += `&q=${encodeURIComponent(query)}`;
        if (pageToken) url += `&pageToken=${pageToken}`;
        const response = await fetch(url, { headers: { "Authorization": `Bearer ${accessToken}` } });
        if (!response.ok) throw new Error("Error fetching forwarded emails: " + response.status);
        const data = await response.json();
        if (!data.messages) throw new Error("No messages in response.");
        nextPageTokenForwarded = data.nextPageToken;
        hasMoreForwardedEmails = !!nextPageTokenForwarded;
        const emailPromises = data.messages.map(message => fetchEmailDetails(message.id));
        const emailDetails = await Promise.all(emailPromises);
        emailDetails.forEach(email => { forwardedEmailsGlobal.push(email); });
        applyFilters();
      } catch (error) {
        console.error('Error fetching forwarded emails by query:', error);
        alert("Error fetching forwarded emails: " + error.message);
      } finally {
        isLoading = false;
      }
    }

    // Initial fetch functions
    async function fetchEmails(pageToken = null) {
      await fetchEmailsByQuery(currentInboxQuery, pageToken);
    }
    async function fetchSentEmails(pageToken = null) {
      await fetchSentEmailsByQuery(currentSentQuery, pageToken);
    }
    async function fetchForwardedEmails(pageToken = null) {
      await fetchForwardedEmailsByQuery(currentForwardedQuery, pageToken);
    }

    // Render emails in a given container while preserving a persistent sentinel element.
    function renderEmails(emails, containerId) {
      const tbody = document.getElementById(containerId);
      let sentinel = document.getElementById(containerId + '-sentinel');
      if (!sentinel) {
        const colspan = (containerId === 'sentEmailTable' || containerId === 'forwardedEmailTable') ? '4' : '5';
        sentinel = document.createElement('tr');
        sentinel.id = containerId + '-sentinel';
        sentinel.innerHTML = `<td colspan="${colspan}" class="py-4"></td>`;
      }
      while (tbody.firstChild) { tbody.removeChild(tbody.firstChild); }
      tbody.appendChild(sentinel);
      emails.sort((a, b) => b.internalDate - a.internalDate).forEach(email => {
        const row = document.createElement('tr');
        row.className = 'cursor-pointer';
        row.onclick = () => window.location.href = `/auto_mail/view_email.html?msgid=${email.messageId}`;
        if (containerId === 'sentEmailTable' || containerId === 'forwardedEmailTable'){
          row.innerHTML = `
            <td class="py-3 px-4 text-sm text-gray-900">${formatEmailDate(email.emailDate)}</td>
            <td class="py-3 px-4 text-sm text-gray-900">${email.emailTo}</td>
            <td class="py-3 px-4 text-sm text-gray-900">${email.emailSubject}</td>
            <td class="py-3 px-4 text-sm text-gray-500">${email.snippet}</td>
          `;
        } else {
          row.innerHTML = `
            <td class="py-3 px-4 text-sm text-gray-900">${formatEmailDate(email.emailDate)}</td>
            <td class="py-3 px-4 text-sm text-gray-900">${email.emailFrom}</td>
            <td class="py-3 px-4 text-sm text-gray-900">${email.emailTo}</td>
            <td class="py-3 px-4 text-sm text-gray-900">${email.emailSubject}</td>
            <td class="py-3 px-4 text-sm text-gray-500">${email.snippet}</td>
          `;
        }
        tbody.insertBefore(row, sentinel);
      });
      // Insert loading rows as needed
      if ((containerId === 'unreadEmailTable' || containerId === 'readEmailTable') && hasMoreInboxEmails) {
        const loadingRow = document.createElement('tr');
        loadingRow.innerHTML = `
          <td colspan="5" class="py-4 text-center ${isLoading ? '' : 'hidden'}">
            <div class="inline-flex items-center">
              <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading more emails...
            </div>
          </td>
        `;
        tbody.insertBefore(loadingRow, sentinel);
      }
      if (containerId === 'sentEmailTable' && hasMoreSentEmails) {
        const loadingRow = document.createElement('tr');
        loadingRow.innerHTML = `
          <td colspan="4" class="py-4 text-center ${isLoading ? '' : 'hidden'}">
            <div class="inline-flex items-center">
              <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading more emails...
            </div>
          </td>
        `;
        tbody.insertBefore(loadingRow, sentinel);
      }
      if (containerId === 'forwardedEmailTable' && hasMoreForwardedEmails) {
        const loadingRow = document.createElement('tr');
        loadingRow.innerHTML = `
          <td colspan="4" class="py-4 text-center ${isLoading ? '' : 'hidden'}">
            <div class="inline-flex items-center">
              <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Loading more emails...
            </div>
          </td>
        `;
        tbody.insertBefore(loadingRow, sentinel);
      }
    }

    // Infinite Scroll Setup using persistent sentinel elements; now use current queries.
    function setupInfiniteScroll() {
      const options = { root: null, rootMargin: '0px', threshold: 0.1 };
      const callback = (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !isLoading) {
            if (entry.target.id === 'sentEmailTable-sentinel' && hasMoreSentEmails) {
              fetchSentEmails(nextPageTokenSent);
            } else if (entry.target.id === 'forwardedEmailTable-sentinel' && hasMoreForwardedEmails) {
              fetchForwardedEmails(nextPageTokenForwarded);
            } else if ((entry.target.id === 'unreadEmailTable-sentinel' || entry.target.id === 'readEmailTable-sentinel') && hasMoreInboxEmails) {
              fetchEmails(nextPageTokenInbox);
            }
          }
        });
      };
      const observer = new IntersectionObserver(callback, options);
      observer.observe(document.getElementById('unreadEmailTable-sentinel'));
      observer.observe(document.getElementById('readEmailTable-sentinel'));
      observer.observe(document.getElementById('sentEmailTable-sentinel'));
      observer.observe(document.getElementById('forwardedEmailTable-sentinel'));
    }

    // Department functions remain unchanged.
    async function loadDepartments() {
      try {
        const response = await fetch('/auto_mail/api/departments.php', {
          headers: { 'Authorization': `Bearer ${accessToken}` }
        });
        const departments = await response.json();
        const departmentList = document.getElementById('departmentList');
        departmentList.innerHTML = `
          <button class="w-full text-left px-4 py-2 rounded-md transition-colors ${currentDepartment === null ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}" onclick="selectDepartment(null)">
            <div class="flex items-center justify-between">
              <span>All Emails</span>
            </div>
          </button>
        `;
        departments.forEach(dept => {
          const button = document.createElement('button');
          button.className = `w-full text-left px-4 py-2 rounded-md transition-colors ${currentDepartment === dept.id ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'}`;
          button.onclick = () => selectDepartment(dept.id);
          button.innerHTML = `
            <div class="flex items-center justify-between">
              <span>${dept.name}</span>
              ${dept.unread_count > 0 ? `<span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">${dept.unread_count}</span>` : ''}
            </div>
          `;
          departmentList.appendChild(button);
        });
      } catch (error) {
        console.error('Error loading departments:', error);
        alert("Error loading departments: " + error.message);
      }
    }

    async function loadKeywords(deptId) {
      try {
        const response = await fetch(`/auto_mail/api/keywords.php?departmentId=${deptId}`, {
          headers: { 'Authorization': `Bearer ${accessToken}` }
        });
        if (!response.ok) throw new Error("Error fetching keywords: " + response.status);
        const keywords = await response.json();
        displayKeywords(keywords);
        applyKeywordFilter();
      } catch (error) {
        console.error('Error loading keywords:', error);
        alert("Error loading keywords: " + error.message);
      }
    }

    function displayKeywords(keywords) {
      const keywordContainer = document.getElementById('keywordContainer');
      keywordContainer.innerHTML = "";
      keywords.forEach(kwObj => {
        const keyword = kwObj.keyword || kwObj;
        const label = document.createElement('label');
        label.className = "inline-flex items-center mr-4";
        label.innerHTML = `
          <input type="checkbox" class="keyword-filter" value="${keyword}" onchange="applyKeywordFilter()" />
          <span class="ml-2 text-gray-700">${keyword}</span>
        `;
        keywordContainer.appendChild(label);
      });
    }

    // Modified applyKeywordFilter to update global query variables for each category.
    function applyKeywordFilter() {
      const selectedKeywords = Array.from(document.querySelectorAll('#keywordContainer input:checked')).map(cb => cb.value);
      let query = selectedKeywords.length > 0 ? selectedKeywords.map(kw => `"${kw}"`).join(" OR ") : "";
      
      // Update global queries for each category.
      currentInboxQuery = query; // For inbox emails, no extra string is added.
      currentSentQuery = query ? "in:sent (" + query + ")" : "in:sent";
      currentForwardedQuery = query ? "in:sent subject:(Fwd:) (" + query + ")" : "in:sent subject:(Fwd:)";
      
      // Reset arrays and fetch emails with current queries.
      allEmailsGlobal = [];
      unreadEmailsGlobal = [];
      readEmailsGlobal = [];
      sentEmailsGlobal = [];
      forwardedEmailsGlobal = [];
      
      fetchEmailsByQuery(currentInboxQuery);
      fetchSentEmailsByQuery(currentSentQuery);
      fetchForwardedEmailsByQuery(currentForwardedQuery);
    }

    function selectDepartment(deptId) {
      currentDepartment = deptId;
      loadDepartments();
      if (deptId) {
        loadKeywords(deptId);
      } else {
        document.getElementById('keywordContainer').innerHTML = "";
        allEmailsGlobal = [];
        unreadEmailsGlobal = [];
        readEmailsGlobal = [];
        fetchEmails();
      }
    }

    // Apply filters to render emails in all tabs.
    function applyFilters() {
      renderEmails(unreadEmailsGlobal, 'unreadEmailTable');
      renderEmails(readEmailsGlobal, 'readEmailTable');
      renderEmails(sentEmailsGlobal, 'sentEmailTable');
      renderEmails(forwardedEmailsGlobal, 'forwardedEmailTable');
    }

    // Tab Switching for showing/hiding containers.
    document.getElementById('tab-unread').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-read').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-read').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-sent').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-sent').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-forwarded').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-forwarded').classList.replace('text-white', 'text-gray-800');
      document.getElementById('unreadContainer').classList.remove('hidden');
      document.getElementById('readContainer').classList.add('hidden');
      document.getElementById('sentContainer').classList.add('hidden');
      document.getElementById('forwardedContainer').classList.add('hidden');
    });
    document.getElementById('tab-read').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-unread').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-unread').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-sent').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-sent').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-forwarded').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-forwarded').classList.replace('text-white', 'text-gray-800');
      document.getElementById('readContainer').classList.remove('hidden');
      document.getElementById('unreadContainer').classList.add('hidden');
      document.getElementById('sentContainer').classList.add('hidden');
      document.getElementById('forwardedContainer').classList.add('hidden');
    });
    document.getElementById('tab-sent').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-unread').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-unread').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-read').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-read').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-forwarded').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-forwarded').classList.replace('text-white', 'text-gray-800');
      document.getElementById('sentContainer').classList.remove('hidden');
      document.getElementById('unreadContainer').classList.add('hidden');
      document.getElementById('readContainer').classList.add('hidden');
      document.getElementById('forwardedContainer').classList.add('hidden');
    });
    document.getElementById('tab-forwarded').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-unread').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-unread').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-read').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-read').classList.replace('text-white', 'text-gray-800');
      document.getElementById('tab-sent').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-sent').classList.replace('text-white', 'text-gray-800');
      document.getElementById('forwardedContainer').classList.remove('hidden');
      document.getElementById('unreadContainer').classList.add('hidden');
      document.getElementById('readContainer').classList.add('hidden');
      document.getElementById('sentContainer').classList.add('hidden');
    });

    // Logout function remains unchanged.
    function logout() {
      fetch("https://oauth2.googleapis.com/revoke?token=" + accessToken, {
        method: 'POST',
        headers: { 'Content-type': 'application/x-www-form-urlencoded' }
      })
      .then(() => {
        localStorage.removeItem('authInfo');
        window.location.href = "/auto_mail/index.html";
      })
      .catch(err => console.error("Error during logout:", err));
    }

    // On DOM load: load departments, fetch emails, and setup infinite scroll.
    document.addEventListener('DOMContentLoaded', () => {
      loadDepartments();
      // Initially, no keyword filter is applied.
      currentInboxQuery = "";
      currentSentQuery = "in:sent";
      currentForwardedQuery = "in:sent subject:(Fwd:)";
      fetchEmailsByQuery(currentInboxQuery);
      fetchSentEmailsByQuery(currentSentQuery);
      fetchForwardedEmailsByQuery(currentForwardedQuery);
      setupInfiniteScroll();
    });
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
