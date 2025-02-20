<?php
require_once 'includes/dbcon.php';

// Fetch the latest access token from the token table
$query = 'SELECT TOP 1 access_token FROM tokens ORDER BY id DESC';
$result = sqlsrv_query($conn, $query);
if ($result === false) {
  die('Error executing query: ' . print_r(sqlsrv_errors(), true));
}
$access_token = '';
if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
  // Try to decode as JSON; if that fails, use the raw value.
  $tokenData = json_decode($row['access_token'], true);
  $access_token = ($tokenData && isset($tokenData['access_token'])) ? $tokenData['access_token'] : $row['access_token'];
}
sqlsrv_free_stmt($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Auto Mail - Email Forwarding System</title>
  <!-- In production, compile Tailwind CSS locally instead of using the CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body { font-family: 'Poppins', sans-serif; }
    /* Department scroll area */
    .dept-scroll {
      overflow-x: auto;
      white-space: nowrap;
      scrollbar-width: thin;
    }
    .dept-scroll::-webkit-scrollbar { height: 6px; }
    .dept-scroll::-webkit-scrollbar-thumb { background-color: #94a3b8; border-radius: 6px; }
    .dept-scroll::-webkit-scrollbar-track { background-color: #f1f5f9; }
    
    .email-row { transition: all 0.2s ease-in-out; }
    .email-row:hover { background-color: #f8fafc; transform: translateX(4px); }
    .keyword-tag { transition: all 0.2s ease; }
    .keyword-tag:hover { transform: scale(1.05); }
    
    /* Table container with custom scrollbar and no horizontal scroll */
    .table-container {
      max-height: 620px;
      overflow-y: auto;
      overflow-x: hidden;
      position: relative;
    }
    .table-container::-webkit-scrollbar {
      width: 8px;
    }
    .table-container::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 4px;
    }
    .table-container::-webkit-scrollbar-thumb {
      background-color: #94a3b8;
      border-radius: 4px;
    }
    
    /* Spinner inside table row */
    .spinner {
      border: 4px solid rgba(0, 0, 0, 0.1);
      border-top-color: #4f46e5;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      animation: spin 1s linear infinite;
      display: inline-block;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    /* Modal: Ensure it appears above table headers */
    #forwardModal { z-index: 10000; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
  <?php include 'includes/header.php'; ?>

  <!-- Department Selection -->
  <div class="dept-scroll bg-white shadow-md p-4 flex space-x-4">
    <!-- "All Departments" tab -->
    <button 
      class="dept-btn inline-block px-6 py-2 bg-blue-600 text-white rounded-lg transition-colors shadow-sm cursor-pointer"
      onclick="selectDepartment('all', 'All Departments')"
    >All Departments</button>
    <?php
    // Fetch departments
    $sql = 'SELECT id, name FROM departments ORDER BY name';
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
      die(print_r(sqlsrv_errors(), true));
    }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
      echo '<button 
                class="dept-btn inline-block px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors shadow-sm cursor-pointer"
                onclick="selectDepartment(' . $row['id'] . ", '" . htmlspecialchars($row['name']) . '\')"
             >' . htmlspecialchars($row['name']) . '</button>';
    }
    ?>
  </div>

  <!-- Main Content Area -->
  <main class="flex-1 p-6 bg-gray-50">
    <!-- Keywords Section -->
    <div id="keywordSection" class="mb-6 bg-white rounded-xl shadow-md p-6">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Keywords Filter</h2>
      <div id="keywordContainer" class="flex flex-wrap gap-3">
        <!-- Keywords loaded dynamically -->
      </div>
    </div>

    <!-- Email List Section -->
    <div class="bg-white rounded-xl shadow-md p-6 relative">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-semibold text-gray-800">Department Emails</h2>
          <p class="text-gray-600 mt-1">Currently viewing: <span id="currentDept" class="text-indigo-600 font-medium">All Departments</span></p>
        </div>
        <div class="flex gap-4">
          <div class="flex gap-2">
            <button id="tab-unread" class="px-5 py-2 rounded-md shadow font-semibold bg-blue-600 text-white focus:outline-none">
              Unread Emails
            </button>
            <button id="tab-read" class="px-5 py-2 rounded-md shadow font-semibold bg-gray-200 text-gray-800 focus:outline-none">
              Read Emails
            </button>
          </div>
          <button class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors shadow-sm flex items-center gap-2"
                  onclick="openModal()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
            </svg>
            Forward Selected
          </button>
        </div>
      </div>

      <!-- Unread Emails Table -->
      <div id="unreadContainer" class="table-container">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100 sticky top-0">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Select</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">From</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
            </tr>
          </thead>
          <tbody id="unreadEmailTable" class="divide-y divide-gray-100">
            <!-- Unread emails and loading row will be loaded here -->
          </tbody>
        </table>
      </div>

      <!-- Read Emails Table -->
      <div id="readContainer" class="table-container hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100 sticky top-0">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Select</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Date</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">From</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">To</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Subject</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Snippet</th>
            </tr>
          </thead>
          <tbody id="readEmailTable" class="divide-y divide-gray-100">
            <!-- Read emails and loading row will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Forward Modal -->
  <div id="forwardModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md relative">
      <div id="modalInitial">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Forward Selected Emails</h3>
        <div class="space-y-4">
          <button class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors" onclick="showSelectUserField()">
            Forward to Specific User
          </button>
          <button class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors" onclick="forwardToAllUsers()">
            Forward to All Users
          </button>
        </div>
        <button class="mt-4 w-full border border-gray-300 px-4 py-2 rounded text-gray-700 hover:bg-gray-50 transition-colors" onclick="closeModal()">
          Cancel
        </button>
      </div>
      <!-- New select user field (hidden initially) -->
      <div id="selectUserField" class="hidden">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Select a User</h3>
        <select id="userSelect" class="w-full border border-gray-300 rounded px-3 py-2 mb-4">
          <option value="">Select a user...</option>
          <option value="user1">User 1</option>
          <option value="user2">User 2</option>
          <option value="user3">User 3</option>
          <!-- Add additional user options as needed -->
        </select>
        <button class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors" onclick="submitForwardToUser()">
          Forward to Selected User
        </button>
        <button class="mt-4 w-full border border-gray-300 px-4 py-2 rounded text-gray-700 hover:bg-gray-50 transition-colors" onclick="closeModal()">
          Cancel
        </button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      let currentDepartment = 'all';
      let nextPageToken = null;
      let isLoading = false;
      let hasMoreEmails = true;
      let currentTab = 'unread';

      // Create a loading row for table bodies.
      function createLoadingRow(text) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.className = "text-center py-4";
        td.innerHTML = `
          <div class="flex items-center justify-center space-x-2">
            <div class="spinner"></div>
            <span class="text-gray-600 text-sm">${text}</span>
          </div>
        `;
        tr.appendChild(td);
        return tr;
      }

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

      // Return an array of selected keywords
      function getSelectedKeywords() {
        return Array.from(document.querySelectorAll('#keywordContainer input[type="checkbox"]:checked'))
                    .map(cb => cb.value);
      }

      // Load keywords for a department using an AJAX call; for "all", clear keywords.
      function loadKeywords(deptId) {
        if(deptId === 'all'){
          document.getElementById('keywordContainer').innerHTML = '';
          document.getElementById('keywordSection').style.display = 'none';
          loadEmails(deptId);
        } else {
          document.getElementById('keywordSection').style.display = 'block';
          let url = './get_keywords.php?department_id=' + deptId;
          fetch(url)
            .then(response => {
              if (!response.ok) { throw new Error('Network response was not ok'); }
              return response.json();
            })
            .then(data => {
              const container = document.getElementById('keywordContainer');
              container.innerHTML = '';
              data.forEach(keyword => {
                const label = document.createElement('label');
                label.className = 'keyword-tag inline-flex items-center bg-gray-100 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors';
                label.innerHTML = `
                  <input type="checkbox" class="form-checkbox mr-2" value="${keyword}" checked onchange="reloadEmails()">
                  <span class="text-sm text-gray-700">${keyword}</span>
                `;
                container.appendChild(label);
              });
              loadEmails(deptId);
            })
            .catch(error => {
              console.error('Error loading keywords:', error);
              document.getElementById('keywordContainer').innerHTML = '<p class="text-red-500">Error loading keywords. Please try again.</p>';
              loadEmails(deptId);
            });
        }
      }

      // Load emails for a department (calls the API endpoint), including keywords if applicable.
      function loadEmails(deptId, pageToken = null) {
        if (isLoading) return;
        isLoading = true;
        
        const params = new URLSearchParams();
        if(deptId !== 'all'){
          params.append('department_id', deptId);
          const keywords = getSelectedKeywords();
          if(keywords.length > 0){
            params.append('keywords', keywords.join(','));
          }
        }
        params.append('is_read', currentTab === 'read' ? 1 : 0);
        if (pageToken) { params.append('pageToken', pageToken); }

        const tableId = currentTab === 'read' ? 'readEmailTable' : 'unreadEmailTable';
        const tableBody = document.getElementById(tableId);

        // Create and insert a loading row.
        let loadingText = pageToken ? "Loading More Emails..." : "Loading Emails...";
        const loadingRow = createLoadingRow(loadingText);
        if(pageToken){
          tableBody.appendChild(loadingRow);
        } else {
          tableBody.innerHTML = '';
          tableBody.appendChild(loadingRow);
        }

        fetch('./api/emails.php?' + params)
          .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok'); }
            return response.json();
          })
          .then(data => {
            if (tableBody.contains(loadingRow)) {
              tableBody.removeChild(loadingRow);
            }
            if (!pageToken) {
              tableBody.innerHTML = '';
            }
            data.emails.forEach(email => {
              const row = document.createElement('tr');
              row.className = 'email-row';
              row.innerHTML = `
                <td class="py-3 px-4">
                  <input type="checkbox" class="form-checkbox" value="${email.id}">
                </td>
                <td class="py-3 px-4 text-sm text-gray-900">${formatEmailDate(email.date)}</td>
                <td class="py-3 px-4 text-sm text-gray-900">${email.from}</td>
                <td class="py-3 px-4 text-sm text-gray-900">${email.to}</td>
                <td class="py-3 px-4 text-sm text-gray-900">${email.subject}</td>
                <td class="py-3 px-4 text-sm text-gray-500">${email.snippet}</td>
              `;
              tableBody.appendChild(row);
            });
            nextPageToken = data.nextPageToken;
            hasMoreEmails = data.hasMore;
            isLoading = false;
          })
          .catch(error => {
            console.error('Error loading emails:', error);
            isLoading = false;
            if (tableBody.contains(loadingRow)) {
              tableBody.removeChild(loadingRow);
            }
          });
      }

      // Reload emails (for example, when keywords change)
      function reloadEmails(){
        nextPageToken = null;
        hasMoreEmails = true;
        if(currentDepartment){
          loadEmails(currentDepartment);
        }
      }

      // Department selection
      function selectDepartment(deptId, deptName) {
        currentDepartment = deptId;
        document.getElementById('currentDept').textContent = deptName;
        nextPageToken = null;
        hasMoreEmails = true;
        if(deptId === 'all'){
          document.getElementById('keywordContainer').innerHTML = '';
          document.getElementById('keywordSection').style.display = 'none';
          loadEmails(deptId);
        } else {
          document.getElementById('keywordSection').style.display = 'block';
          loadKeywords(deptId);
        }
        document.querySelectorAll('.dept-btn').forEach(btn => {
          if (btn.textContent.trim() === deptName) {
            btn.classList.remove('bg-gray-100', 'text-gray-700');
            btn.classList.add('bg-blue-600', 'text-white');
          } else {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-100', 'text-gray-700');
          }
        });
      }

      // Tab switching
      document.getElementById('tab-unread').addEventListener('click', function() {
        this.classList.add('bg-blue-600', 'text-white');
        this.classList.remove('bg-gray-200', 'text-gray-800');
        document.getElementById('tab-read').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('tab-read').classList.add('bg-gray-200', 'text-gray-800');
        document.getElementById('unreadContainer').classList.remove('hidden');
        document.getElementById('readContainer').classList.add('hidden');
        currentTab = 'unread';
        nextPageToken = null;
        if (currentDepartment) { loadEmails(currentDepartment); }
      });
      document.getElementById('tab-read').addEventListener('click', function() {
        this.classList.add('bg-blue-600', 'text-white');
        this.classList.remove('bg-gray-200', 'text-gray-800');
        document.getElementById('tab-unread').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('tab-unread').classList.add('bg-gray-200', 'text-gray-800');
        document.getElementById('readContainer').classList.remove('hidden');
        document.getElementById('unreadContainer').classList.add('hidden');
        currentTab = 'read';
        nextPageToken = null;
        if (currentDepartment) { loadEmails(currentDepartment); }
      });

      // Infinite scroll within the table container
      function handleScroll(e) {
        const container = e.target;
        if (container.scrollHeight - container.scrollTop <= container.clientHeight + 100 && hasMoreEmails && !isLoading && currentDepartment) {
          loadEmails(currentDepartment, nextPageToken);
        }
      }
      document.querySelectorAll('.table-container').forEach(container => {
        container.addEventListener('scroll', handleScroll);
      });

      // Modal functions
      function openModal() { 
        document.getElementById('forwardModal').classList.remove('hidden'); 
        document.getElementById('modalInitial').style.display = 'block';
        document.getElementById('selectUserField').classList.add('hidden');
      }
      function closeModal() { 
        document.getElementById('forwardModal').classList.add('hidden'); 
      }
      function showSelectUserField() {
        document.getElementById('modalInitial').style.display = 'none';
        document.getElementById('selectUserField').classList.remove('hidden');
      }
      function submitForwardToUser() {
        const selectedUser = document.getElementById('userSelect').value;
        if (!selectedUser) {
          alert('Please select a user.');
          return;
        }
        const selectedEmails = getSelectedEmails();
        if (selectedEmails.length === 0) {
          alert('Please select at least one email to forward');
          return;
        }
        alert(`Forwarding ${selectedEmails.length} email(s) to ${selectedUser}...`);
        closeModal();
      }
      function forwardToAllUsers() {
        const selectedEmails = getSelectedEmails();
        if (selectedEmails.length === 0) {
          alert('Please select at least one email to forward');
          return;
        }
        alert(`Forwarding ${selectedEmails.length} email(s) to all users...`);
        closeModal();
      }
      function getSelectedEmails() {
        const tableId = currentTab === 'read' ? 'readEmailTable' : 'unreadEmailTable';
        return Array.from(document.querySelectorAll(`#${tableId} input[type="checkbox"]:checked`))
               .map(cb => cb.value);
      }

      // Expose functions to global scope for inline onclick handlers.
      window.selectDepartment = selectDepartment;
      window.reloadEmails = reloadEmails;
      window.openModal = openModal;
      window.closeModal = closeModal;
      window.showSelectUserField = showSelectUserField;
      window.submitForwardToUser = submitForwardToUser;
      window.forwardToAllUsers = forwardToAllUsers;

      // Auto-select "All Departments" on load
      selectDepartment('all', "All Departments");
    });

    // Make the access token available to JS
    const accessToken = '<?php echo $access_token; ?>';
    if (!accessToken) {
      alert('Access token not found. Please sign in again.');
      window.location.href = 'index.php';
    }
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
