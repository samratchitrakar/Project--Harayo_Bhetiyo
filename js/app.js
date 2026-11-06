// Cascading location data
const locations = {
    "Kathmandu": ["Thamel", "Basantapur", "Asan", "Ratnapark", "Baneshwor", "Koteshwor", "Balaju", "Kalanki", "Chabahil", "Lazimpat", "New Road", "Maharajgunj"],
    "Lalitpur": ["Patan Dhoka", "Mangalbazar", "Pulchowk", "Jawalakhel", "Ekantakuna", "Satdobato", "Kumaripati", "Lagankhel", "Sanepa", "Tikathali"],
    "Bhaktapur": ["Durbar Square", "Taumadhi", "Suryabinayak", "Kamal Binayak", "Sallaghari", "Lokanthali", "Sipadol", "Thimi"]
};

// Populate area dropdown based on district
function populateAreas(districtId, areaId, selectedArea) {
    const districtEl = document.getElementById(districtId);
    const areaEl = document.getElementById(areaId);
    if (!districtEl || !areaEl) return;

    districtEl.addEventListener('change', function () {
        const district = this.value;
        areaEl.innerHTML = '<option value="">-- Select Area --</option>';
        if (locations[district]) {
            locations[district].forEach(area => {
                const opt = document.createElement('option');
                opt.value = area;
                opt.textContent = area;
                if (area === selectedArea) opt.selected = true;
                areaEl.appendChild(opt);
            });
        }
    });

    // Trigger on load if district already selected
    if (districtEl.value) {
        districtEl.dispatchEvent(new Event('change'));
    }
}

// Initialize dropdowns on page load
document.addEventListener('DOMContentLoaded', function () {
    populateAreas('district', 'area', '');

    // Filter dropdowns on index page
    const filterDistrict = document.getElementById('filter_district');
    const filterArea = document.getElementById('filter_area');

    if (filterDistrict && filterArea) {
        filterDistrict.addEventListener('change', function () {
            const district = this.value;
            filterArea.innerHTML = '<option value="">-- All Areas --</option>';
            if (locations[district]) {
                locations[district].forEach(area => {
                    const opt = document.createElement('option');
                    opt.value = area;
                    opt.textContent = area;
                    filterArea.appendChild(opt);
                });
            }
        });
    }
});
