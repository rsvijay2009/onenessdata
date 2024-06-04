const cards = document.querySelectorAll('.join-card');

cards.forEach(card => {
card.addEventListener('click', () => {
    cards.forEach(c => c.style.border = '1px solid #d4cfcf'); // Remove border from all cards
    card.style.border = '4px solid green'; // Add green border to the clicked card
    document.getElementById('joinSection').style.display = 'block';
});
});

const joinTable1 = document.getElementById('joinTable1');
const joinTable2 = document.getElementById('joinTable2');
const button = document.getElementById('joinBtn');
const joinRelation = document.getElementById('joinRelation');

function checkDropdownValues() {
    if (joinTable1.value === "" || joinTable2.value === "") {
        button.style.display = 'none';
        joinRelation.style.display = 'none';
    } else {
        button.style.display = 'block';
        joinRelation.style.display = 'block';
    }
}

joinTable1.addEventListener('change', checkDropdownValues);
joinTable2.addEventListener('change', checkDropdownValues);

// Initial check to set button visibility on page load
checkDropdownValues();