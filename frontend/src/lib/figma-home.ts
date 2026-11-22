/** Homepage content & assets exported from the SHOP.CO Figma freebie */

export const homeLogo = "/images/home/logo.png";
export const homeVector = "/images/home/vector.png";

export const homeHero = {
  title: "FIND CLOTHES THAT MATCHES YOUR STYLE",
  subtitle:
    "Browse through our diverse range of meticulously crafted garments, designed to bring out your individuality and cater to your sense of style.",
  desktopImage: "/images/home/header-homepage.png",
  mobileImage: "/images/home/header-homepage.png",
  vector: "/images/home/vector.png",
  stats: [
    { value: "200+", label: "International Brands" },
    { value: "2,000+", label: "High-Quality Products" },
    { value: "30,000+", label: "Happy Customers" },
  ],
};

export const homeBrands = [
  { name: "Versace", src: "/icons/brands/versace-logo.svg" },
  { name: "Zara", src: "/icons/brands/zara-logo.svg" },
  { name: "Gucci", src: "/icons/brands/gucci-logo.svg" },
  { name: "Prada", src: "/icons/brands/prada-logo.svg" },
  { name: "Calvin Klein", src: "/icons/brands/calvin-klein-logo.svg" },
];

export const homeNewArrivals = [
  {
    name: "T-shirt with Tape Details",
    price: 120,
    compareAt: null as number | null,
    rating: 4.5,
    image: "/images/home/pic1.png",
    href: "/products?sort=newest",
  },
  {
    name: "Skinny Fit Jeans",
    price: 240,
    compareAt: 260,
    rating: 3.5,
    image: "/images/home/pic2.png",
    href: "/products?sort=newest",
  },
  {
    name: "Checkered Shirt",
    price: 180,
    compareAt: null,
    rating: 4.5,
    image: "/images/home/pic3.png",
    href: "/products?sort=newest",
  },
  {
    name: "Sleeve Striped T-shirt",
    price: 130,
    compareAt: 160,
    rating: 4.5,
    image: "/images/home/pic4.png",
    href: "/products?sort=newest",
  },
];

export const homeTopSelling = [
  {
    name: "Vertical Striped Shirt",
    price: 212,
    compareAt: 232,
    rating: 5.0,
    image: "/images/home/pic5.png",
    href: "/products?sort=price_desc",
  },
  {
    name: "Courage Graphic T-shirt",
    price: 145,
    compareAt: null,
    rating: 4.0,
    image: "/images/home/pic6.png",
    href: "/products?sort=price_desc",
  },
  {
    name: "Loose Fit Bermuda Shorts",
    price: 80,
    compareAt: null,
    rating: 3.0,
    image: "/images/home/pic7.png",
    href: "/products?sort=price_desc",
  },
  {
    name: "Faded Skinny Jeans",
    price: 210,
    compareAt: null,
    rating: 4.5,
    image: "/images/home/pic8.png",
    href: "/products?sort=price_desc",
  },
];

/** Figma asymmetric grid: row1 short+wide, row2 wide+short */
export const homeStyles = [
  {
    name: "Casual",
    image: "/images/home/dress-style-1.png",
    href: "/products?dress_style=Casual",
    span: "short" as const,
  },
  {
    name: "Formal",
    image: "/images/home/dress-style-2.png",
    href: "/products?dress_style=Formal",
    span: "wide" as const,
  },
  {
    name: "Party",
    image: "/images/home/dress-style-3.png",
    href: "/products?dress_style=Party",
    span: "wide" as const,
  },
  {
    name: "Gym",
    image: "/images/home/dress-style-4.png",
    href: "/products?dress_style=Gym",
    span: "short" as const,
  },
];

export const homeTestimonials = [
  {
    name: "Sarah M.",
    text: "I'm blown away by the quality and style of the clothes I received from Shop.co. From casual wear to elegant dresses, every piece I've bought has exceeded my expectations.",
  },
  {
    name: "Alex K.",
    text: "Finding clothes that align with my personal style used to be a challenge until I discovered Shop.co. The range of options they offer is truly remarkable, catering to a variety of tastes and occasions.",
  },
  {
    name: "James L.",
    text: "As someone who's always on the lookout for unique fashion pieces, I'm thrilled to have stumbled upon Shop.co. The selection of clothes is not only diverse but also on-point with the latest trends.",
  },
  {
    name: "Mooen.",
    text: "As someone who's always on the lookout for unique fashion pieces, I'm thrilled to have stumbled upon Shop.co. The selection of clothes is not only diverse but also on-point with the latest trends.",
  },
  {
    name: "Samantha D.",
    text: "I absolutely love the quality and fit of everything I've ordered. Shipping was fast and customer support was incredibly helpful whenever I had a question.",
  },
];
