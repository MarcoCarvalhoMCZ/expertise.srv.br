const menuBtn = document.getElementById('menuBtn');
const menu = document.getElementById('menu');
const year = document.getElementById('year');

year.textContent = new Date().getFullYear();

menuBtn.addEventListener('click', () => {
  menu.classList.toggle('open');
});

menu.querySelectorAll('a').forEach((link) => {
  link.addEventListener('click', () => menu.classList.remove('open'));
});

// ==================== Blog Loader ====================
async function loadBlogPosts() {
  const grid = document.getElementById('blogGrid');
  if (!grid) return;

  try {
    const res = await fetch('/api/posts');
    if (!res.ok) throw new Error('Erro ao carregar posts');
    const data = await res.json();
    const posts = data.posts || [];

    if (posts.length === 0) {
      grid.innerHTML = '<article class="card" style="text-align:center;padding:48px;"><p style="color:#94a3b8;">Nenhum post publicado ainda.</p></article>';
      return;
    }

    grid.innerHTML = posts.map(post => {
      const typeIcon = {
        text: '📄',
        image: '🖼️',
        video: '🎬',
        audio: '🎙️',
        mixed: '📰'
      };
      const excerpt = post.excerpt || stripHtml(post.content || '').substring(0, 150) + '...';
      const thumbUrl = post.thumbnail_url || (post.media && post.media.length > 0 ? post.media[0].file_url : '');
      const date = new Date(post.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });

      return `
        <article class="card blog-card">
          ${thumbUrl ? `<div class="blog-thumb"><img src="${thumbUrl}" alt="${escapeHtml(post.title)}" loading="lazy" /></div>` : ''}
          <span class="icon">${typeIcon[post.post_type] || '📄'}</span>
          <h3><a href="/post/${post.slug}" style="color:inherit;text-decoration:none;">${escapeHtml(post.title)}</a></h3>
          <p>${escapeHtml(excerpt)}</p>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
            <small style="color:#94a3b8;">${date}</small>
            ${post.author_name ? `<small style="color:#64748b;">${escapeHtml(post.author_name)}</small>` : ''}
          </div>
        </article>`;
    }).join('');
  } catch (err) {
    console.error('Erro ao carregar blog:', err);
    grid.innerHTML = '<article class="card" style="text-align:center;padding:48px;"><p style="color:#ef4444;">Erro ao carregar posts do blog.</p></article>';
  }
}

function stripHtml(html) {
  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}

function escapeHtml(text) {
  const map = { '&': '&', '<': '<', '>': '>', '"': '"', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Carregar posts ao iniciar
document.addEventListener('DOMContentLoaded', loadBlogPosts);