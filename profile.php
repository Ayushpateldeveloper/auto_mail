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
    body {
      font-family: 'Inter', sans-serif;
    }
    /* Scrollable table container */
    .table-container {
      max-height: 620px;
      overflow-y: auto;
    }
    /* Smooth hover effect for table rows */
    tr:hover {
      background-color: #f3f4f6;
    }
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
      </div>

      <!-- Unread Emails -->
      <div id="unreadContainer" class="bg-white rounded-lg shadow p-6 h-[74vh] w-[72vw]">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Unread Emails</h2>
        <div class="table-container">
          <table class="min-w-full divide-y divide-gray-200  ">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">From</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
                <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
              </tr>
            </thead>
            <tbody id="unreadEmailTable" class="divide-y divide-gray-100" >
              <!-- Unread emails will be inserted here -->
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
              <!-- Read emails will be inserted here -->
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Global variables for emails and pagination
    let allEmailsGlobal = [];
    let unreadEmailsGlobal = [];
    let readEmailsGlobal = [];
    let currentDepartment = null;
    let nextPageToken = null;
    let isLoading = false;
    let hasMoreEmails = true;

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
        success: function(response) {
          console.log(response);
        },
        error: function(xhr, status, error) {
          console.error('Error storing authInfo:', error);
        }
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

    // Fetch email details
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

    // Fetch emails by query
    async function fetchEmailsByQuery(query = "", pageToken = null) {
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
        nextPageToken = data.nextPageToken;
        hasMoreEmails = !!nextPageToken;
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

    // Initial email fetch
    async function fetchEmails(pageToken = null) {
      await fetchEmailsByQuery("", pageToken);
    }

    // Render emails
    function renderEmails(emails, containerId) {
      const tbody = document.getElementById(containerId);
      // Keep a sentinel element for infinite scroll
      const sentinel = tbody.lastElementChild;
      tbody.innerHTML = '';
      emails.sort((a, b) => b.internalDate - a.internalDate).forEach(email => {
        const row = document.createElement('tr');
        row.className = 'cursor-pointer';
        row.onclick = () => window.location.href = `/auto_mail/view_email.html?msgid=${email.messageId}`;
        row.innerHTML = `
          <td class="py-3 px-4 text-sm text-gray-900">${formatEmailDate(email.emailDate)}</td>
          <td class="py-3 px-4 text-sm text-gray-900">${email.emailFrom}</td>
          <td class="py-3 px-4 text-sm text-gray-900">${email.emailTo}</td>
          <td class="py-3 px-4 text-sm text-gray-900">${email.emailSubject}</td>
          <td class="py-3 px-4 text-sm text-gray-500">${email.snippet}</td>
        `;
        tbody.appendChild(row);
      });
      tbody.appendChild(sentinel);
      if (hasMoreEmails) {
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
    }

    // Infinite Scroll Setup
    function setupInfiniteScroll() {
      const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
      };
      const callback = (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !isLoading && hasMoreEmails) {
            const selectedKeywords = Array.from(document.querySelectorAll('#keywordContainer input:checked')).map(cb => cb.value);
            let query = selectedKeywords.length > 0 ? selectedKeywords.map(kw => `"${kw}"`).join(" OR ") : "";
            fetchEmailsByQuery(query, nextPageToken);
          }
        });
      };
      const observer = new IntersectionObserver(callback, options);
      // Create and append sentinels if they don't already exist
      const unreadSentinel = document.createElement('tr');
      unreadSentinel.id = 'unread-sentinel';
      unreadSentinel.innerHTML = '<td colspan="5" class="py-4"></td>';
      if (!document.getElementById('unread-sentinel')) {
        document.getElementById('unreadEmailTable').appendChild(unreadSentinel);
      }
      const readSentinel = document.createElement('tr');
      readSentinel.id = 'read-sentinel';
      readSentinel.innerHTML = '<td colspan="5" class="py-4"></td>';
      if (!document.getElementById('read-sentinel')) {
        document.getElementById('readEmailTable').appendChild(readSentinel);
      }
      observer.observe(unreadSentinel);
      observer.observe(readSentinel);
    }

    // Department functions
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

    function applyKeywordFilter() {
      const selectedKeywords = Array.from(document.querySelectorAll('#keywordContainer input:checked')).map(cb => cb.value);
      let query = selectedKeywords.length > 0 ? selectedKeywords.map(kw => `"${kw}"`).join(" OR ") : "";
      allEmailsGlobal = [];
      unreadEmailsGlobal = [];
      readEmailsGlobal = [];
      fetchEmailsByQuery(query);
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

    // Apply filters
    function applyFilters() {
      renderEmails(unreadEmailsGlobal, 'unreadEmailTable');
      renderEmails(readEmailsGlobal, 'readEmailTable');
    }

    // Tab Switching
    document.getElementById('tab-unread').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-read').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-read').classList.replace('text-white', 'text-gray-800');
      document.getElementById('unreadContainer').classList.remove('hidden');
      document.getElementById('readContainer').classList.add('hidden');
    });

    document.getElementById('tab-read').addEventListener('click', function () {
      this.classList.replace('bg-gray-200', 'bg-blue-600');
      this.classList.replace('text-gray-800', 'text-white');
      document.getElementById('tab-unread').classList.replace('bg-blue-600', 'bg-gray-200');
      document.getElementById('tab-unread').classList.replace('text-white', 'text-gray-800');
      document.getElementById('readContainer').classList.remove('hidden');
      document.getElementById('unreadContainer').classList.add('hidden');
    });

    // Logout function
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

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', () => {
      loadDepartments();
      fetchEmails();
      setupInfiniteScroll();
    });
  </script>
</body>
</html>
