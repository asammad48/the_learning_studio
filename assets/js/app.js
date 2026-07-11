const subjects = [
  { title: 'Business & Management', category: 'Business & Management', lessons: 32, description: 'Planning, organizing, leading and controlling — the foundations of every organization.', href: 'subject-business.html' },
  { title: 'Human Resource Management', category: 'Business & Management', lessons: 24, description: 'Hiring, motivation, performance and the people side of business.', href: 'subject-business.html' },
  { title: 'Organizational Behavior', category: 'Business & Management', lessons: 18, description: 'How individuals and teams actually behave inside organizations.', href: 'subject-business.html' },
  { title: 'Strategic Management', category: 'Business & Management', lessons: 20, description: 'Vision, competitive advantage and long-term decision making.', href: 'subject-business.html' },
  { title: 'Entrepreneurship', category: 'Business & Management', lessons: 16, description: 'Ideas, validation, operations and growth for new ventures.', href: 'subject-business.html' },
  { title: 'Project Management', category: 'Business & Management', lessons: 22, description: 'Plan tasks, manage risk and deliver projects with confidence.', href: 'subject-business.html' },
  { title: 'Artificial Intelligence', category: 'AI & Data', lessons: 28, description: 'What AI is, how it learns and where it is heading.', href: 'subject-ai.html' },
  { title: 'Data Analytics', category: 'AI & Data', lessons: 22, description: 'Turn raw information into useful insights and dashboards.', href: 'subject-ai.html' },
  { title: 'Machine Learning', category: 'AI & Data', lessons: 24, description: 'Models, training data and evaluation explained step by step.', href: 'subject-ai.html' },
  { title: 'Statistics', category: 'AI & Data', lessons: 18, description: 'Use probability and evidence to reason about uncertainty.', href: 'subject-ai.html' },
  { title: 'Computer Science', category: 'Technology & Computer Science', lessons: 30, description: 'Algorithms, systems and software thinking for modern learners.', href: 'subjects.html' },
  { title: 'Web Development', category: 'Technology & Computer Science', lessons: 26, description: 'Build responsive websites with HTML, CSS and JavaScript.', href: 'subjects.html' },
  { title: 'Cybersecurity', category: 'Technology & Computer Science', lessons: 19, description: 'Protect systems, people and data from common threats.', href: 'subjects.html' },
  { title: 'Cloud Computing', category: 'Technology & Computer Science', lessons: 17, description: 'Deploy scalable apps and services with modern cloud tools.', href: 'subjects.html' },
  { title: 'Finance Basics', category: 'Finance & Accounting', lessons: 19, description: 'Budgets, markets, cash flow and financial decision making.', href: 'subjects.html' },
  { title: 'Accounting', category: 'Finance & Accounting', lessons: 21, description: 'Records, statements and the language of business performance.', href: 'subjects.html' },
  { title: 'Investing', category: 'Finance & Accounting', lessons: 15, description: 'Risk, return, diversification and long-term planning.', href: 'subjects.html' },
  { title: 'Digital Marketing', category: 'Marketing & Digital Skills', lessons: 17, description: 'Campaigns, channels and measurement for online growth.', href: 'subjects.html' },
  { title: 'Content Strategy', category: 'Marketing & Digital Skills', lessons: 13, description: 'Plan useful content that reaches the right audience.', href: 'subjects.html' },
  { title: 'Personal Branding', category: 'Marketing & Digital Skills', lessons: 12, description: 'Communicate your value with clarity and consistency.', href: 'subjects.html' },
  { title: 'Psychology', category: 'Psychology & Education', lessons: 16, description: 'Behavior, learning, memory and human development.', href: 'subjects.html' },
  { title: 'Instructional Design', category: 'Psychology & Education', lessons: 14, description: 'Create lessons, activities and assessments that work.', href: 'subjects.html' },
  { title: 'Learning Science', category: 'Psychology & Education', lessons: 15, description: 'Evidence-based approaches to study, practice and recall.', href: 'subjects.html' },
  { title: 'Supply Chain', category: 'Supply Chain & Operations', lessons: 15, description: 'Move products, information and value through reliable systems.', href: 'subjects.html' },
  { title: 'Operations Management', category: 'Supply Chain & Operations', lessons: 18, description: 'Improve processes, quality and capacity across teams.', href: 'subjects.html' },
  { title: 'Logistics', category: 'Supply Chain & Operations', lessons: 12, description: 'Coordinate transportation, warehousing and delivery.', href: 'subjects.html' },
  { title: 'Health Science', category: 'Health & Medical', lessons: 14, description: 'Foundations of health, care systems and medical terminology.', href: 'subjects.html' },
  { title: 'Public Health', category: 'Health & Medical', lessons: 13, description: 'Understand prevention, populations and community health.', href: 'subjects.html' }
];

const categories = ['All Subjects', ...new Set(subjects.map((subject) => subject.category))];
let activeCategory = 'All Subjects';
let searchTerm = '';

const filterContainer = document.querySelector('#categoryFilters');
const groupsContainer = document.querySelector('#subjectGroups');
const shownCount = document.querySelector('#shownCount');
const subjectSearch = document.querySelector('#subjectSearch');
const clearSearch = document.querySelector('#clearSearch');
const menuToggle = document.querySelector('.menu-toggle');
const navPanel = document.querySelector('.nav-panel');
const businessCards = document.querySelector('#businessCards');

function cardTemplate(subject) {
  return `
    <article class="card">
      <div class="card-top"><span class="card-icon">${subject.title.charAt(0)}</span><small>${subject.category}</small></div>
      <h3>${subject.title}</h3>
      <p>${subject.description}</p>
      <div class="card-footer"><span>${subject.lessons} lessons</span><a href="${subject.href}">Explore →</a></div>
    </article>
  `;
}

function renderFilters() {
  if (!filterContainer) return;
  filterContainer.innerHTML = categories.map((category) => `
    <button class="${category === activeCategory ? 'active' : ''}" type="button" data-category="${category}">${category}</button>
  `).join('');
}

function filteredSubjects() {
  return subjects.filter((subject) => {
    const matchesCategory = activeCategory === 'All Subjects' || subject.category === activeCategory;
    const haystack = `${subject.title} ${subject.category} ${subject.description}`.toLowerCase();
    return matchesCategory && haystack.includes(searchTerm.toLowerCase());
  });
}

function renderSubjects() {
  if (!groupsContainer || !shownCount) return;
  const filtered = filteredSubjects();
  shownCount.textContent = filtered.length;
  const grouped = filtered.reduce((groups, subject) => {
    groups[subject.category] ||= [];
    groups[subject.category].push(subject);
    return groups;
  }, {});

  groupsContainer.innerHTML = Object.entries(grouped).map(([category, items]) => `
    <section class="subject-group">
      <div class="group-heading"><h2>${category}</h2></div>
      <div class="cards">${items.map(cardTemplate).join('')}</div>
    </section>
  `).join('') || '<p>No subjects match your search yet.</p>';
}

function renderBusinessCards() {
  if (!businessCards) return;
  businessCards.innerHTML = subjects
    .filter((subject) => subject.category === 'Business & Management')
    .slice(0, 4)
    .map(cardTemplate)
    .join('');
}

filterContainer?.addEventListener('click', (event) => {
  const button = event.target.closest('button[data-category]');
  if (!button) return;
  activeCategory = button.dataset.category;
  renderFilters();
  renderSubjects();
});

subjectSearch?.addEventListener('input', (event) => {
  searchTerm = event.target.value;
  renderSubjects();
});

clearSearch?.addEventListener('click', () => {
  searchTerm = '';
  subjectSearch.value = '';
  renderSubjects();
});

menuToggle?.addEventListener('click', () => {
  const isOpen = navPanel.classList.toggle('open');
  menuToggle.setAttribute('aria-expanded', String(isOpen));
});

document.querySelectorAll('.question button').forEach((button) => {
  button.addEventListener('click', () => {
    const question = button.closest('.question');
    question.querySelectorAll('button').forEach((choice) => choice.classList.remove('selected', 'correct'));
    button.classList.add('selected');
    question.querySelector(`[data-option="${question.dataset.answer}"]`)?.classList.add('correct');
    updateScore();
  });
});

function updateScore() {
  const scoreText = document.querySelector('#scoreText');
  if (!scoreText) return;
  const questions = [...document.querySelectorAll('.question')];
  const score = questions.filter((question) => {
    const selected = question.querySelector('.selected');
    return selected && selected.dataset.option === question.dataset.answer;
  }).length;
  scoreText.textContent = `You scored ${score} / ${questions.length}`;
}

document.querySelector('#tryAgain')?.addEventListener('click', () => {
  document.querySelectorAll('.question button').forEach((button) => button.classList.remove('selected', 'correct'));
  updateScore();
});

document.querySelectorAll('.question button[data-default="true"]').forEach((button) => {
  button.classList.add('selected');
  button.classList.add('correct');
});

renderFilters();
renderSubjects();
renderBusinessCards();
updateScore();
