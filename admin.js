
    // Navigasi antar section
    function showSection(id) {
      document.querySelectorAll('.section-content').forEach(sec => sec.style.display = 'none');
      document.getElementById(id).style.display = 'block';
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      event.target.classList.add('active');
    }

    // Data event tersimpan
    let eventData = {};

    function bukaFormTambah() {
      const form = document.getElementById('formTambahEvent');
      form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function tambahEvent() {
      const tanggal = document.getElementById('tanggalEvent').value;
      const nama = document.getElementById('namaEvent').value;

      if (!tanggal || !nama) {
        alert('Isi tanggal dan nama event!');
        return;
      }

      eventData[tanggal] = nama;

      const cell = [...document.querySelectorAll('#kalender td')].find(td => td.textContent.trim() === tanggal);
      if (cell) {
        const span = document.createElement('span');
        span.classList.add('event-day');
        span.textContent = nama;
        cell.appendChild(span);
      }

      document.getElementById('tanggalEvent').value = '';
      document.getElementById('namaEvent').value = '';
      document.getElementById('formTambahEvent').style.display = 'none';
    }

    function simpanEvent() {
      if (Object.keys(eventData).length === 0) {
        alert('Belum ada event yang ditambahkan.');
      } else {
        alert('Data event berhasil disimpan ke kalender!');
      }
    }