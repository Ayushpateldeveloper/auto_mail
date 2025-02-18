<?php
require_once 'includes/dbcon.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_department':
                $name = $_POST['name'];
                $sql = "INSERT INTO departments (name) VALUES ('$name')";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;

            case 'edit_department':
                $id = (int) $_POST['id'];
                $name = $_POST['name'];
                $sql = "UPDATE departments SET name='$name' WHERE id=$id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;

            case 'delete_department':
                $id = (int) $_POST['id'];
                $sql = "DELETE FROM departments WHERE id=$id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;

            case 'add_keyword':
                if (isset($_POST['keywords'])) {
                    foreach ($_POST['keywords'] as $keyword) {
                        $department_id = (int) $_POST['department_id'];
                        $sql = "INSERT INTO keywords (keyword, department_id) VALUES ('$keyword', $department_id)";
                        $stmt = sqlsrv_query($conn, $sql);
                        if ($stmt === false) {
                            die(print_r(sqlsrv_errors(), true));
                        }
                    }
                }
                break;

            case 'delete_keyword':
                $id = (int) $_POST['id'];
                $sql = "DELETE FROM keywords WHERE id=$id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;

            case 'edit_keyword':
                $id = (int) $_POST['keyword_id'];
                $keyword = $_POST['keyword'];
                $sql = "UPDATE keywords SET keyword='$keyword' WHERE id=$id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;

            // New action: Edit aggregated keywords for a department
            case 'edit_department_keywords':
                $dept_id = (int) $_POST['department_id'];
                $keywords_str = $_POST['keywords'];  // comma-separated string
                // Delete existing keywords for this department
                $sql = "DELETE FROM keywords WHERE department_id = $dept_id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                // Insert new keywords (if any)
                $keywords_array = array_filter(array_map('trim', explode(',', $keywords_str)));
                foreach ($keywords_array as $kw) {
                    $sql = "INSERT INTO keywords (keyword, department_id) VALUES ('$kw', $dept_id)";
                    $stmt = sqlsrv_query($conn, $sql);
                    if ($stmt === false) {
                        die(print_r(sqlsrv_errors(), true));
                    }
                }
                break;

            // New action: Delete all keywords for a department
            case 'delete_department_keywords':
                $dept_id = (int) $_POST['department_id'];
                $sql = "DELETE FROM keywords WHERE department_id = $dept_id";
                $stmt = sqlsrv_query($conn, $sql);
                if ($stmt === false) {
                    die(print_r(sqlsrv_errors(), true));
                }
                break;
        }
        header('Location: department.php');
        exit();
    }
}

// (Optional) Create tables if they do not exist
$sql = "IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'departments')
BEGIN
    CREATE TABLE departments (
        id INT IDENTITY(1,1) PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT GETDATE()
    )
END";
sqlsrv_query($conn, $sql);

$sql = "IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'keywords')
BEGIN
    CREATE TABLE keywords (
        id INT IDENTITY(1,1) PRIMARY KEY,
        department_id INT NULL,
        keyword VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_Department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    )
END";
sqlsrv_query($conn, $sql);

// Fetch departments for display (basic data)
$sql = 'SELECT * FROM departments ORDER BY name';
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$departments = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $departments[] = $row;
}

// Fetch keywords for detailed display (each keyword row)
$sql = 'SELECT k.*, d.name as department_name FROM keywords k LEFT JOIN departments d ON k.department_id = d.id ORDER BY d.name, k.keyword';
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$keywords = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $keywords[] = $row;
}

// Fetch aggregated keywords by department for display (only departments with keywords)
$sql = "SELECT d.id, d.name AS department_name, 
               STUFF((SELECT ', ' + k.keyword 
                      FROM keywords k 
                      WHERE k.department_id = d.id 
                      FOR XML PATH('')), 1, 2, '') AS keywords
        FROM departments d 
        WHERE EXISTS (SELECT 1 FROM keywords k WHERE k.department_id = d.id)
        ORDER BY d.name";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$aggregatedKeywords = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $aggregatedKeywords[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Department & Keyword Management</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
  <!-- Header -->
  <header class="bg-white shadow">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-100 text-blue-600">
          <i class="fas fa-layer-group text-xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800">Department Management</h1>
      </div>
      <a href="./profile.html" class="bg-blue-600 text-white px-5 py-2 rounded shadow hover:bg-blue-700 transition">
        <i class="fas fa-arrow-left mr-2"></i>
        Back to Dashboard
      </a>
    </div>
  </header>

  <div class="container mx-auto px-6 py-8 space-y-8">
    <!-- Top Section: Two Side-by-Side Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Add Department Card -->
      <div class="bg-white rounded-lg shadow-xl p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Add Department</h2>
        <form method="POST" id="addDepartmentForm" class="flex flex-col gap-4">
          <input type="hidden" name="action" value="add_department">
          <input type="text" name="name" placeholder="Department Name" required class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>Add Department
          </button>
        </form>
      </div>
      <!-- Add Keyword Card -->
      <div class="bg-white rounded-lg shadow-xl p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Add Keyword</h2>
        <form method="POST" id="addKeywordForm" class="flex flex-col gap-4">
          <input type="hidden" name="action" value="add_keyword">
          <!-- Remove "required" so previously added bubble isn't cleared -->
          <input type="text" id="keywordInput" name="keyword" placeholder="Enter keywords, separated by commas" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <div id="keywordInputBubbles" class="flex flex-wrap gap-3"></div>
          <select name="department_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Select Department</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>Add Keyword(s)
          </button>
        </form>
      </div>
    </div>

    <!-- Full Width Card: Departments Table -->
    <div class="bg-white rounded-lg shadow-xl p-6">
      <h2 class="text-2xl font-bold mb-4 text-gray-800">Departments</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created At</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($departments as $dept): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="px-6 py-4 whitespace-nowrap text-gray-800"><?php echo htmlspecialchars($dept['name']); ?></td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                <?php
                $created = is_object($dept['created_at'])
                    ? $dept['created_at']->format('M d, Y H:i')
                    : date('M d, Y H:i', strtotime($dept['created_at']));
                echo htmlspecialchars($created);
                ?>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button type="button" onclick="openEditDeptModal(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 mr-3">
                  <i class="fas fa-edit"></i>
                </button>
                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this department? This will also delete all associated keywords.')">
                  <input type="hidden" name="action" value="delete_department">
                  <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                  <button type="submit" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Full Width Card: Aggregated Keywords Table -->
    <div class="bg-white rounded-lg shadow-xl p-6">
      <h2 class="text-2xl font-bold mb-4 text-gray-800">Keywords by Department</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keywords</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (count($aggregatedKeywords) == 0): ?>
            <tr>
              <td colspan="3" class="px-6 py-4 text-center text-gray-600">No keyword found</td>
            </tr>
            <?php else: ?>
              <?php foreach ($aggregatedKeywords as $agg): ?>
              <tr class="hover:bg-gray-100 transition">
                <td class="px-6 py-4 whitespace-nowrap text-gray-800"><?php echo htmlspecialchars($agg['department_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-800"><?php echo htmlspecialchars($agg['keywords']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <!-- Edit aggregated keywords modal trigger -->
                  <button type="button" onclick="openEditDeptKeywordsModal(<?php echo $agg['id']; ?>, '<?php echo htmlspecialchars($agg['keywords'], ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 mr-3">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Delete all keywords for this department -->
                  <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete all keywords for this department?')">
                    <input type="hidden" name="action" value="delete_department_keywords">
                    <input type="hidden" name="department_id" value="<?php echo $agg['id']; ?>">
                    <button type="submit" class="text-red-600 hover:text-red-800">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Edit Department Modal -->
  <div id="editDeptModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-80">
      <h3 class="text-xl font-bold mb-4 text-gray-800">Edit Department</h3>
      <form id="editDeptForm" method="POST">
        <input type="hidden" name="action" value="edit_department">
        <input type="hidden" name="id" id="editDeptId">
        <div class="mb-4">
          <label for="editDeptName" class="block text-gray-700 font-medium mb-2">Department Name</label>
          <input type="text" name="name" id="editDeptName" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex justify-end">
          <button type="button" onclick="closeEditDeptModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg mr-3">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Keyword Modal (for individual keyword editing) -->
  <div id="editKeywordModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-80">
      <h3 class="text-xl font-bold mb-4 text-gray-800">Edit Keyword</h3>
      <form id="editKeywordForm" method="POST">
        <input type="hidden" name="action" value="edit_keyword">
        <input type="hidden" name="keyword_id" id="editKeywordId">
        <div class="mb-4">
          <label for="editKeywordName" class="block text-gray-700 font-medium mb-2">Keyword</label>
          <input type="text" name="keyword" id="editKeywordName" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex justify-end">
          <button type="button" onclick="closeEditKeywordModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg mr-3">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Aggregated Keywords Modal -->
  <div id="editDeptKeywordsModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-96">
      <h3 class="text-xl font-bold mb-4 text-gray-800">Edit Keywords for Department</h3>
      <form id="editDeptKeywordsForm" method="POST">
        <input type="hidden" name="action" value="edit_department_keywords">
        <input type="hidden" name="department_id" id="editDeptKeywordsDeptId">
        <div class="mb-4">
          <label for="editDeptKeywordsInput" class="block text-gray-700 font-medium mb-2">Keywords (comma-separated)</label>
          <input type="text" name="keywords" id="editDeptKeywordsInput" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex justify-end">
          <button type="button" onclick="closeEditDeptKeywordsModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg mr-3">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal functions for department
    function openEditDeptModal(id, name) {
      document.getElementById('editDeptId').value = id;
      document.getElementById('editDeptName').value = name;
      document.getElementById('editDeptModal').classList.remove('hidden');
    }
    function closeEditDeptModal() {
      document.getElementById('editDeptModal').classList.add('hidden');
    }

    // Modal functions for individual keyword
    function openEditKeywordModal(id, keyword) {
      document.getElementById('editKeywordId').value = id;
      document.getElementById('editKeywordName').value = keyword;
      document.getElementById('editKeywordModal').classList.remove('hidden');
    }
    function closeEditKeywordModal() {
      document.getElementById('editKeywordModal').classList.add('hidden');
    }

    // Modal functions for aggregated keywords (per department)
    function openEditDeptKeywordsModal(deptId, keywords) {
      document.getElementById('editDeptKeywordsDeptId').value = deptId;
      document.getElementById('editDeptKeywordsInput').value = keywords;
      document.getElementById('editDeptKeywordsModal').classList.remove('hidden');
    }
    function closeEditDeptKeywordsModal() {
      document.getElementById('editDeptKeywordsModal').classList.add('hidden');
    }
  </script>

  <script>
    // Add Keyword Bubble functionality for the "Add Keyword" form
    document.addEventListener("DOMContentLoaded", function () {
      const keywordInput = document.getElementById("keywordInput");
      const keywordInputBubbles = document.getElementById("keywordInputBubbles");
      const keywordForm = document.getElementById("addKeywordForm");

      keywordInput.addEventListener("keypress", function (event) {
        if (event.key === "," || event.key === "Enter") {
          event.preventDefault();
          addKeywordBubble();
        }
      });
      keywordInput.addEventListener("blur", addKeywordBubble);

      function addKeywordBubble() {
        const input = keywordInput.value.trim();
        if (input) {
          const keywords = input.split(",").map(kw => kw.trim()).filter(kw => kw);
          keywords.forEach(keyword => {
            createKeywordBubble(keyword);
          });
          keywordInput.value = "";
        }
      }
      function createKeywordBubble(keyword) {
        const bubble = document.createElement("span");
        bubble.className = "bg-blue-100 text-blue-600 px-3 py-1 rounded-full inline-flex items-center";
        bubble.innerHTML = `${keyword} <button type="button" class="ml-2 text-red-500 hover:text-red-700 text-sm">&times;</button>`;
        bubble.querySelector("button").addEventListener("click", function () {
          bubble.remove();
        });
        keywordInputBubbles.appendChild(bubble);
      }

      keywordForm.addEventListener("submit", function (event) {
        // Remove any previously appended hidden inputs for keywords
        const existingHiddenInputs = keywordForm.querySelectorAll('input[type="hidden"][name="keywords[]"]');
        existingHiddenInputs.forEach(input => input.remove());
        const bubbles = keywordInputBubbles.querySelectorAll("span");
        if (bubbles.length === 0) {
          alert("Please enter at least one keyword.");
          event.preventDefault();
          return;
        }
        bubbles.forEach(bubble => {
          const keyword = bubble.textContent.replace("×", "").trim();
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = "keywords[]";
          input.value = keyword;
          keywordForm.appendChild(input);
        });
      });
    });
  </script>

  <script>
    // (Optional) Add fade-in animation for tables
    document.addEventListener('DOMContentLoaded', function() {
      const tables = document.querySelectorAll('table');
      tables.forEach(table => {
        table.classList.add('transition', 'duration-500', 'ease-in');
      });
    });
  </script>
</body>
</html>
<?php sqlsrv_close($conn); ?>
