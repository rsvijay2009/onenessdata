const cards = document.querySelectorAll('.join-card');

cards.forEach(card => {
card.addEventListener('click', () => {
    cards.forEach(c => c.style.backgroundColor = 'transparent'); // Remove border from all cards
    card.style.backgroundColor  = '#EAE6EA'; // Add green border to the clicked card
    document.getElementById('joinSection').style.display = 'block';
    const joinTypeText = card.querySelector('.joinTypeClass').textContent;
    console.log(joinTypeText);
    document.getElementById('joinType').value = joinTypeText;
});
});

const joinTable1 = document.getElementById('joinTable1');
const joinTable2 = document.getElementById('joinTable2');
const button = document.getElementById('joinBtn');
const joinRelation = document.getElementById('joinRelation');

function checkDropdownValues() {
    if (joinTable1.value === "" || joinTable2.value === "") {
        button.style.display = 'none';
    } else {
        button.style.display = 'block';
    }
}

joinTable1.addEventListener('change', checkDropdownValues);
joinTable2.addEventListener('change', checkDropdownValues);

// Initial check to set button visibility on page load
checkDropdownValues();