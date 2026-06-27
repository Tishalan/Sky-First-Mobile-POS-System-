let products = JSON.parse(localStorage.getItem('products')) || [
    { id: 'P001', name: 'iPhone 13 Pro', category: 'Smartphone', originalPrice: 1099.00, price: 999.00, stock: 15, barcode: '123456789001', color: 'Silver', photo: null, soldCount: 0, lastSoldDate: null },
    { id: 'P002', name: 'Samsung Galaxy S21', category: 'Smartphone', originalPrice: 949.00, price: 849.00, stock: 8, barcode: '123456789002', color: 'Black', photo: null, soldCount: 0, lastSoldDate: null },
    { id: 'P003', name: 'Wireless Headphones', category: 'Accessories', originalPrice: 99.99, price: 89.99, stock: 32, barcode: '123456789003', color: 'White', photo: null, soldCount: 0, lastSoldDate: null },
    { id: 'P004', name: 'Fast Charger', category: 'Accessories', originalPrice: 34.99, price: 29.99, stock: 45, barcode: '123456789004', color: 'Black', photo: null, soldCount: 0, lastSoldDate: null },
    { id: 'P005', name: 'Phone Case', category: 'Accessories', originalPrice: 24.99, price: 19.99, stock: 62, barcode: '123456789005', color: 'Blue', photo: null, soldCount: 0, lastSoldDate: null },
    { id: 'P006', name: 'Screen Protector', category: 'Accessories', originalPrice: 14.99, price: 9.99, stock: 78, barcode: '123456789006', color: 'Clear', photo: null, soldCount: 0, lastSoldDate: null }
];

let sales = JSON.parse(localStorage.getItem('sales')) || [];
let lastBillNumber = parseInt(localStorage.getItem('lastBillNumber')) || 1237;

function saveData() {
    localStorage.setItem('products', JSON.stringify(products));
    localStorage.setItem('sales', JSON.stringify(sales));
    localStorage.setItem('lastBillNumber', lastBillNumber);
}